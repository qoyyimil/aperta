<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Exceptions\InvalidStatusTransition;
use App\Features\Booking\Filament\Pages\ViewBooking;
use App\Features\Proofs\AddProofs;
use App\Features\Proofs\Filament\ProofsRelationManager;
use App\Jobs\RenderPhoto;
use App\Models\Booking;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
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

    public function test_huge_image_is_rejected_before_decoding(): void
    {
        Storage::fake('local');
        $image = imagecreatetruecolor(1, 1);
        ob_start();
        imagepng($image);
        // Header PNG mengaku 20000x20000 (400 MP) walau isinya kecil.
        $bomb = substr_replace((string) ob_get_clean(), pack('NN', 20_000, 20_000), 16, 8);
        Storage::disk('local')->put('proof-uploads/x/BOM.PNG', $bomb);
        $proof = $this
            ->shotBooking()
            ->proofs()
            ->create([
                'frame' => 1,
                'filename' => 'BOM.PNG',
                'source_path' => 'proof-uploads/x/BOM.PNG',
            ]);

        new RenderPhoto($proof)->handle();

        $this->assertStringContainsString('terlalu besar', (string) $proof->fresh()->error);
        $this->assertFalse($proof->fresh()->isReady());
    }

    public function test_upload_cannot_take_over_files_of_another_booking(): void
    {
        Storage::fake('local');
        Queue::fake();
        $photographer = User::factory()->create(['role' => UserRole::Photographer]);
        $mine = $this->shotBooking();
        $mine->crew()->attach($photographer);
        $foreign = 'results/' . Booking::factory()->create()->id . '/renders/1-preview.jpg';
        Storage::disk('local')->put($foreign, 'foto booking lain');

        // Path file lain dikirim langsung sebagai state, tanpa upload sungguhan.
        Livewire::actingAs($photographer)
            ->test(ViewBooking::class, ['record' => $mine->getRouteKey()])
            ->callAction('uploadProofs', ['files' => ['x' => $foreign]]);

        $this->assertSame(0, $mine->proofs()->count());
        Storage::disk('local')->assertExists($foreign);

        $mine->forceFill(['status' => BookingStatus::Editing])->save();
        Livewire::actingAs($photographer)
            ->test(ViewBooking::class, ['record' => $mine->getRouteKey()])
            ->callAction('uploadResults', ['files' => ['x' => $foreign]]);

        $this->assertSame(0, $mine->results()->count());
    }

    public function test_only_the_assigned_photographer_can_delete_proofs(): void
    {
        $booking = $this->shotBooking();
        $proof = $booking
            ->proofs()
            ->create(['frame' => 1, 'filename' => 'A.JPG', 'preview_path' => 'p', 'thumb_path' => 't']);
        $photographer = User::factory()->create(['role' => UserRole::Photographer]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $booking->crew()->attach([$photographer->id, $editor->id]);
        $proofsTab = fn(User $user) => Livewire::actingAs($user)->test(ProofsRelationManager::class, [
            'ownerRecord' => $booking,
            'pageClass' => ViewBooking::class,
        ]);

        $proofsTab($editor)->assertActionHidden(TestAction::make('delete')->table($proof));
        $proofsTab($photographer)->assertActionVisible(TestAction::make('delete')->table($proof));
    }
}
