<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Package;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Pelunasan tagihan sesi wajib paling lambat H-1 sebelum sesi. */
class SessionPaymentTest extends TestCase
{
    use RefreshDatabase;

    private function booking(): Booking
    {
        $package = Package::factory()->create(['price' => 1_000_000, 'edit_quota' => 1, 'extra_photo_price' => 25_000]);

        return Booking::factory()->forPackage($package)->create(['session_date' => today()->addDays(5)]);
    }

    public function test_due_date_is_one_day_before_session(): void
    {
        $this->assertSame(today()->addDays(4)->toDateString(), $this->booking()->paymentDueOn()->toDateString());
    }

    public function test_deposit_is_due_within_a_day_but_never_after_full_payment_due(): void
    {
        $this->travelTo('2026-11-05 20:00');

        $this->assertSame('2026-11-06 20:00', $this->booking()->depositDueAt()->format('Y-m-d H:i'));

        $tomorrow = Booking::factory()->create(['session_date' => '2026-11-06']);
        $this->assertSame('2026-11-05 23:59', $tomorrow->depositDueAt()->format('Y-m-d H:i'));
    }

    public function test_session_cannot_be_marked_done_until_session_bill_is_paid(): void
    {
        $booking = $this->booking();
        $booking->payments()->create(['amount' => 500_000, 'paid_on' => today(), 'method' => 'transfer']);
        $booking->refresh();

        $this->assertSame(BookingStatus::Confirmed, $booking->status);
        $this->assertNotNull($booking->transitionBlocker(BookingStatus::Shot));

        try {
            $booking->transitionTo(BookingStatus::Shot);
            $this->fail('Transisi seharusnya ditolak.');
        } catch (DomainException) {
            $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        }

        $booking->payments()->create(['amount' => 500_000, 'paid_on' => today(), 'method' => 'transfer']);
        $this->assertSame(BookingStatus::Shot, $booking->fresh()->transitionTo(BookingStatus::Shot)->status);
    }

    public function test_extra_photos_are_not_part_of_the_session_bill(): void
    {
        $booking = Booking::factory()->forPackage(Package::factory()->create(['price' => 1_000_000, 'edit_quota' => 1, 'extra_photo_price' => 25_000]))->paid()->create();
        foreach ([1, 2, 3] as $i) {
            $booking->proofs()->create(['frame' => $i, 'filename' => "D{$i}.JPG", 'is_selected' => true]);
        }

        $this->assertTrue($booking->isSessionPaid());
        $this->assertSame(1_050_000, $booking->price()->total());
    }

    public function test_overdue_when_due_date_passed_without_full_payment(): void
    {
        $booking = $this->booking();
        $this->assertFalse($booking->isPaymentOverdue());

        $this->travelTo(today()->addDays(5));
        $this->assertTrue($booking->isPaymentOverdue());
    }
}