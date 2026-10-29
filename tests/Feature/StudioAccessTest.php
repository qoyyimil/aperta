<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Features\Booking\Filament\BookingResource;
use App\Features\Booking\Filament\Pages\EditBooking;
use App\Features\Booking\Filament\Pages\ListBookings;
use App\Features\Catalog\Filament\Addons\ManageAddons;
use App\Features\Catalog\Filament\BlockedDates\ManageBlockedDates;
use App\Features\Catalog\Filament\Packages\ManagePackages;
use App\Features\Catalog\Filament\Packages\PackageResource;
use App\Models\Addon;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class StudioAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(UserRole $role, array $attrs = []): User
    {
        return User::factory()
            ->withMfa()
            ->create(['role' => $role] + $attrs);
    }

    public function test_admin_must_set_up_mfa_but_crew_does_not(): void
    {
        $this
            ->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get(PackageResource::getUrl())
            ->assertRedirectContains('multi-factor-authentication');
        $this
            ->actingAs(User::factory()->create(['role' => UserRole::Photographer]))
            ->get('/studio')
            ->assertOk();
    }

    public function test_photographer_only_sees_assigned_bookings(): void
    {
        $me = $this->user(UserRole::Photographer);
        $mine = Booking::factory()->create();
        $mine->crew()->attach($me);
        $other = Booking::factory()->create();

        $this->actingAs($me);

        Livewire::test(ListBookings::class)->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$other]);

        $this->get(BookingResource::getUrl('view', ['record' => $other]))->assertNotFound();
        $this->get(BookingResource::getUrl('create'))->assertForbidden();
        $this->get(BookingResource::getUrl('edit', ['record' => $mine]))->assertForbidden();
    }

    public function test_catalog_is_admin_only_and_inactive_users_are_locked_out(): void
    {
        $this->actingAs($this->user(UserRole::Photographer))->get(PackageResource::getUrl())->assertForbidden();
        $this->actingAs($this->user(UserRole::Admin))->get(PackageResource::getUrl())->assertOk();
        $this
            ->actingAs($this->user(UserRole::Admin, ['is_active' => false]))
            ->get(PackageResource::getUrl())
            ->assertForbidden();
    }

    public function test_simple_resource_handles_create_edit_and_delete(): void
    {
        $this->actingAs($this->user(UserRole::Admin));
        $page = Livewire::test(ManageAddons::class);

        $page->callAction('create', ['name' => 'Cetak 10R', 'price' => 60_000, 'max_qty' => 5, 'is_active' => true]);
        $addon = Addon::sole();
        $this->assertSame('Cetak 10R', $addon->name);

        $page->callAction(TestAction::make('edit')->table($addon), ['name' => 'Cetak 12R']);
        $this->assertSame('Cetak 12R', $addon->fresh()->name);

        $page->callAction(TestAction::make('delete')->table($addon));
        $this->assertSame(0, Addon::count());

        $holidays = Livewire::test(ManageBlockedDates::class);
        $holidays->callAction('create', ['date' => '2026-12-25'])->assertHasNoFormErrors();
        $holidays->callAction('create', ['date' => '2026-12-25'])->assertHasFormErrors(['date' => 'unique']);
        $this->assertSame(1, BlockedDate::count());

        $package = Package::factory()->create(['price' => 750_000, 'duration_minutes' => 120]);
        Livewire::test(ManagePackages::class)
            ->assertCanSeeTableRecords([$package])
            ->assertSee('750.000')
            ->assertDontSee('750.000,00')
            ->assertSee('120 menit');
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

    public function test_admin_can_fix_client_details_from_booking_edit(): void
    {
        $booking = Booking::factory()->create();
        $this->actingAs($this->user(UserRole::Admin));

        Livewire::test(EditBooking::class, ['record' => $booking->getRouteKey()])
            ->assertFormFieldVisible('client_id')
            ->assertFormFieldHidden('package_id')
            ->callAction(TestAction::make('editOption')->schemaComponent('client_id'), [
                'name' => 'Thalia Putri',
                'email' => 'benar@example.com',
                'whatsapp' => '+62 812-3456-7890',
            ])
            ->assertHasNoErrors();

        $this->assertSame('benar@example.com', $booking->client->fresh()->email);
    }

    public function test_reschedule_and_cancel_are_recorded(): void
    {
        $booking = Booking::factory()->create(['session_date' => '2026-11-08', 'starts_at' => '09:00']);

        $booking->update(['session_date' => '2026-12-01', 'starts_at' => '13:00']);
        $booking->transitionTo(BookingStatus::Cancelled, 'Klien sakit');

        $this->assertDatabaseHas('booking_logs', [
            'action' => 'reschedule',
            'reason' => '2026-11-08 09:00 → 2026-12-01 13:00',
        ]);
        $this->assertSame('Klien sakit', $booking->fresh()->cancel_reason);
    }
}
