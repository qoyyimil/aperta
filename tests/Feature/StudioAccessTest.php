<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Filament\Resources\Bookings\BookingResource;
use App\Filament\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resources\Packages\PackageResource;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudioAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(UserRole $role, array $attrs = []): User
    {
        return User::factory()->create(['role' => $role] + $attrs);
    }

    public function test_photographer_only_sees_assigned_bookings(): void
    {
        $me = $this->user(UserRole::Photographer);
        $mine = Booking::factory()->create();
        $mine->crew()->attach($me);
        $other = Booking::factory()->create();

        $this->actingAs($me);

        Livewire::test(ListBookings::class)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$other]);

        $this->get(BookingResource::getUrl('view', ['record' => $other]))->assertNotFound();
        $this->get(BookingResource::getUrl('create'))->assertForbidden();
        $this->get(BookingResource::getUrl('edit', ['record' => $mine]))->assertForbidden();
    }

    public function test_catalog_is_admin_only_and_inactive_users_are_locked_out(): void
    {
        $this->actingAs($this->user(UserRole::Photographer))->get(PackageResource::getUrl())->assertForbidden();
        $this->actingAs($this->user(UserRole::Admin))->get(PackageResource::getUrl())->assertOk();
        $this->actingAs($this->user(UserRole::Admin, ['is_active' => false]))->get(PackageResource::getUrl())->assertForbidden();
    }

    public function test_only_assigned_photographer_can_mark_session_done(): void
    {
        $photographer = $this->user(UserRole::Photographer);
        $booking = Booking::factory()->create();
        $booking->crew()->attach($photographer);

        $this->assertTrue($photographer->can('transition', [$booking, BookingStatus::Shot]));
        $this->assertFalse($photographer->can('transition', [$booking, BookingStatus::Cancelled]));
        $this->assertFalse($this->user(UserRole::Editor)->can('transition', [$booking, BookingStatus::Shot]));
        $this->assertTrue($this->user(UserRole::Admin)->can('transition', [$booking, BookingStatus::Cancelled]));
    }

    public function test_reschedule_and_cancel_are_recorded(): void
    {
        $booking = Booking::factory()->create(['session_date' => '2026-11-08', 'starts_at' => '09:00']);

        $booking->update(['session_date' => '2026-12-01', 'starts_at' => '13:00']);
        $booking->transitionTo(BookingStatus::Cancelled, 'Klien sakit');

        $this->assertDatabaseHas('booking_logs', ['action' => 'reschedule', 'reason' => '2026-11-08 09:00 → 2026-12-01 13:00']);
        $this->assertSame('Klien sakit', $booking->fresh()->cancel_reason);
    }
}
