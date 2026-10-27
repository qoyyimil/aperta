<?php

namespace Tests\Feature;

use App\Actions\AddResults;
use App\Enums\BookingStatus;
use App\Jobs\RenderPhoto;
use App\Models\Booking;
use App\Models\EditedPhoto;
use App\Models\Package;
use App\Support\ProofImages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;
use ZipArchive;

class ResultDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $package = Package::factory()->create(['price' => 1_000_000, 'edit_quota' => 5]);
        $this->booking = Booking::factory()->forPackage($package)->create();
        foreach (['DSC_45', 'DSC_4512', 'DSC_4513'] as $i => $name) {
            $this->booking
                ->proofs()
                ->create([
                    'frame' => $i + 1,
                    'filename' => "{$name}.JPG",
                    'preview_path' => "p{$i}",
                    'thumb_path' => "t{$i}",
                    'is_selected' => true,
                ]);
        }
        $this->booking->forceFill(['status' => BookingStatus::Editing])->save(); // langsung ke tahap editing
    }

    private function upload(string $name): string
    {
        $path = 'results/' . uniqid() . "/{$name}";
        Storage::disk('local')->put($path, UploadedFile::fake()->image($name, 1200, 800)->getContent());

        return $path;
    }

    private function deliver(): EditedPhoto
    {
        Queue::fake();
        new AddResults()->handle($this->booking, [$this->upload('DSC_4512_edit.jpg')]);
        $result = $this->booking->results()->sole();
        $result->update(['preview_path' => 'x', 'thumb_path' => 'y']);
        $this->booking->deliverResults();

        return $result;
    }

    public function test_results_match_the_longest_selected_name_and_track_missing(): void
    {
        Queue::fake();
        $result = new AddResults()->handle($this->booking, [
            $this->upload('DSC_4512_edit.jpg'),
            $this->upload('lainnya.jpg'),
        ]);

        $this->assertSame(['added' => 2, 'matched' => 1], $result);
        $this->assertSame(
            'DSC_4512.JPG',
            $this->booking
                ->results()
                ->where('filename', 'DSC_4512_edit.jpg')
                ->sole()
                ->proof
                ->filename,
        );
        $this->assertSame(
            ['DSC_45.JPG', 'DSC_4513.JPG'],
            $this->booking->missingResults()->orderBy('frame')->pluck('filename')->all(),
        );
        Queue::assertPushed(RenderPhoto::class, 2);
    }

    public function test_render_job_keeps_original_for_download(): void
    {
        // Gambar putih, karena watermark hitam tidak terlihat di gambar palsu yang hitam.
        $image = imagecreatetruecolor(1200, 800);
        imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
        ob_start();
        imagejpeg($image);
        $path = 'results/' . uniqid() . '/DSC_4513_edit.jpg';
        Storage::disk('local')->put($path, ob_get_clean());
        $result = $this->booking->results()->create(['filename' => 'DSC_4513_edit.jpg', 'path' => $path, 'size' => 1]);

        new RenderPhoto($result)->handle();

        $this->assertTrue($result->fresh()->isReady());
        Storage::disk('local')->assertExists($path);

        // Pratinjau berwatermark agar tidak bisa disimpan gratis sebelum lunas.
        $original = Storage::disk('local')->get($path);
        $preview = Storage::disk('local')->get($result->fresh()->preview_path);
        $this->assertSame(ProofImages::make()->render($original, config('aperta.watermark'))['preview'], $preview);
        $this->assertNotSame(ProofImages::make()->render($original)['preview'], $preview);
    }

    public function test_results_are_visible_after_delivery_but_downloads_need_full_payment(): void
    {
        $result = $this->deliver();
        $base = "/lacak/{$this->booking->access_token}/hasil";

        $this->assertSame(BookingStatus::Delivered, $this->booking->fresh()->status);
        $this->get($base)->assertInertia(
            fn(Assert $page) => $page
                ->component('Gallery/Results')
                ->where('booking.canDownload', false)
                ->where('booking.remaining', 1_000_000)
                ->where('results.0.frame', 2),
        );
        $this->get("{$base}/{$result->id}/unduh")->assertForbidden();

        $this->booking->payments()->create(['amount' => 1_000_000, 'paid_on' => today(), 'method' => 'transfer']);

        $this->get("{$base}/{$result->id}/unduh")->assertOk()->assertDownload('DSC_4512_edit.jpg');
        $zip = $this->get("{$base}/zip")->assertOk();
        $archive = new ZipArchive();
        $archive->open($zip->baseResponse->getFile()->getPathname());
        $this->assertSame('DSC_4512_edit.jpg', $archive->getNameIndex(0));
    }

    public function test_zip_keeps_every_file_when_names_clash(): void
    {
        $this->deliver();
        foreach (['DSC_4512_edit.jpg', 'DSC_4512_edit-2.jpg'] as $name) {
            $this->booking->results()->create(['filename' => $name, 'path' => $this->upload($name), 'size' => 1]);
        }
        $this->booking->payments()->create(['amount' => 1_000_000, 'paid_on' => today(), 'method' => 'transfer']);

        $zip = $this->get("/lacak/{$this->booking->access_token}/hasil/zip")->assertOk();
        $archive = new ZipArchive();
        $archive->open($zip->baseResponse->getFile()->getPathname());

        $this->assertSame(3, $archive->numFiles);
    }

    public function test_result_of_another_booking_cannot_be_downloaded(): void
    {
        $this->deliver();
        $this->booking->payments()->create(['amount' => 1_000_000, 'paid_on' => today(), 'method' => 'transfer']);
        $foreign = Booking::factory()->create()->results()->create(['filename' => 'x.jpg', 'path' => 'x', 'size' => 1]);

        $this->get("/lacak/{$this->booking->access_token}/hasil/{$foreign->id}/unduh")->assertNotFound();
    }

    public function test_cannot_deliver_without_results(): void
    {
        $this->expectExceptionMessage('Unggah minimal satu hasil edit');

        $this->booking->deliverResults();
    }
}
