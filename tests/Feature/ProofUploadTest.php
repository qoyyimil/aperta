<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Exceptions\InvalidStatusTransition;
use App\Features\Proofs\AddProofs;
use App\Jobs\RenderPhoto;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProofUploadTest extends TestCase
{
    use RefreshDatabase;

    private function shotBooking(): Booking
    {
        return Booking::factory()->paid()->create()->transitionTo(BookingStatus::Shot);
    }

    private function upload(string $name): string
    {
        $path = 'proof-uploads/' . uniqid() . "/{$name}";
        Storage::disk('local')->put($path, UploadedFile::fake()->image($name, 3000, 2000)->getContent());

        return $path;
    }

    public function test_frames_follow_camera_order_and_duplicates_are_skipped(): void
    {
        Storage::fake('local');
        Queue::fake();
        $booking = $this->shotBooking();
        $add = new AddProofs();

        $add->handle($booking, [$this->upload('DSC_0010.JPG'), $this->upload('DSC_0009.JPG')]);
        $result = $add->handle($booking, [$dup = $this->upload('dsc_0009.jpg'), $this->upload('DSC_0011.JPG')]);

        $this->assertSame(
            ['DSC_0009.JPG', 'DSC_0010.JPG', 'DSC_0011.JPG'],
            $booking->proofs()->orderBy('frame')->pluck('filename')->all(),
        );
        $this->assertSame(['added' => 1, 'skipped' => ['dsc_0009.jpg']], $result);
        Storage::disk('local')->assertMissing($dup);
        Queue::assertPushed(RenderPhoto::class, 3);
    }

    public function test_job_creates_preview_and_thumb_and_removes_original(): void
    {
        Storage::fake('local');
        $booking = $this->shotBooking();
        $source = $this->upload('DSC_4512.JPG');
        $proof = $booking->proofs()->create(['frame' => 1, 'filename' => 'DSC_4512.JPG', 'source_path' => $source]);

        new RenderPhoto($proof)->handle();

        $proof->refresh();
        $this->assertTrue($proof->isReady());
        Storage::disk('local')->assertMissing($source);
        [$width, $height] = getimagesizefromstring(Storage::disk('local')->get($proof->preview_path));
        $this->assertSame([2048, 1365], [$width, $height]);
        $this->assertSame(480, getimagesizefromstring(Storage::disk('local')->get($proof->thumb_path))[0]);
    }

    public function test_publish_opens_selection_with_final_quota(): void
    {
        $booking = $this->shotBooking();
        $booking->proofs()->create(['frame' => 1, 'filename' => 'A.JPG', 'preview_path' => 'p', 'thumb_path' => 't']);

        $this->assertFalse($booking->hasPendingProofs());
        $booking->publishProofs(25, 30_000, '2026-12-01');

        $booking->refresh();
        $this->assertSame(BookingStatus::Selecting, $booking->status);
        $this->assertSame([25, 30_000, '2026-12-01'], [
            $booking->edit_quota,
            $booking->extra_photo_price,
            $booking->selection_deadline->toDateString(),
        ]);
    }

    public function test_cannot_publish_before_session_is_done(): void
    {
        $this->expectException(InvalidStatusTransition::class);

        Booking::factory()->create()->publishProofs(20, 25_000, '2026-12-01');
    }

    public function test_crew_assignment_is_logged_and_grants_proof_access(): void
    {
        $booking = Booking::factory()->create();
        $photographer = User::factory()->create(['name' => 'Ryan', 'role' => UserRole::Photographer]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->assertFalse($photographer->can('manageProofs', $booking));
        $booking->assignCrew([$photographer->id, $editor->id]);

        $this->assertTrue($photographer->can('manageProofs', $booking->fresh()));
        $this->assertFalse($editor->can('manageProofs', $booking->fresh()));
        $this->assertDatabaseHas('booking_logs', ['booking_id' => $booking->id, 'action' => 'crew']);
    }
}
