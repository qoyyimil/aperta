<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Models\Booking;
use App\Models\EditedPhoto;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_booking_gets_code_token_and_package_snapshot(): void
    {
        $package = Package::factory()->create(['price' => 750_000, 'edit_quota' => 20]);
        $booking = Booking::factory()->forPackage($package)->create();

        $this->assertMatchesRegularExpression('/^AP-\w{6}$/', $booking->code);
        $this->assertSame(48, strlen($booking->access_token));
        $this->assertSame(BookingStatus::New, $booking->status);

        $package->update(['price' => 999_000]);
        $this->assertSame(750_000, $booking->fresh()->package_price);
    }

    public function test_deposit_confirms_booking_and_is_logged(): void
    {
        $booking = Booking::factory()->forPackage(Package::factory()->create(['price' => 1_000_000]))->create();

        $booking->payments()->create(['amount' => 400_000, 'paid_on' => today(), 'method' => 'transfer']);
        $this->assertSame(BookingStatus::New, $booking->fresh()->status);

        $booking->payments()->create(['amount' => 100_000, 'paid_on' => today(), 'method' => 'transfer']);
        $this->assertSame(BookingStatus::Confirmed, $booking->fresh()->status);
        $this->assertDatabaseHas('booking_logs', ['booking_id' => $booking->id, 'to_status' => 'confirmed']);
    }

    public function test_invalid_transition_is_rejected(): void
    {
        $this->expectException(InvalidStatusTransition::class);

        Booking::factory()->create()->transitionTo(BookingStatus::Editing);
    }

    public function test_photos_above_quota_are_charged(): void
    {
        $package = Package::factory()->create(['price' => 750_000, 'edit_quota' => 2, 'extra_photo_price' => 25_000]);
        $booking = Booking::factory()->forPackage($package)->create();

        foreach (range(1, 5) as $i) {
            $booking->proofs()->create(['frame' => $i, 'filename' => "DSC_{$i}.JPG", 'is_selected' => $i <= 4]);
        }

        $this->assertSame(2, $booking->extraPhotos());
        $this->assertSame(800_000, $booking->price()->total());
    }

    public function test_note_selects_photo_and_result_matches_by_filename(): void
    {
        $booking = Booking::factory()->create();
        $proof = $booking->proofs()->create(['frame' => 1, 'filename' => 'DSC_4512.JPG']);

        $this->assertTrue($proof->annotate('rapikan rambut')->is_selected);

        $result = new EditedPhoto(['filename' => 'dsc_4512_edit.jpg', 'path' => 'x', 'size' => 1]);
        $result->booking()->associate($booking);

        $this->assertTrue($result->matchProof()->proof->is($proof));
    }
}
