<?php

namespace App\Features\Delivery\Http;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\EditedPhoto;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/** Galeri & unduhan hasil edit untuk klien (fitur DLV). Unduhan hanya setelah lunas (BR-6). */
class ResultController extends Controller
{
    public function show(Booking $booking): Response
    {
        abort_unless($booking->status === BookingStatus::Delivered, 404);

        return Inertia::render('Gallery/Results', ResultsView::of($booking));
    }

    public function download(Booking $booking, EditedPhoto $result): StreamedResponse
    {
        $this->ensurePaid($booking);

        return Storage::disk(config('aperta.photo_disk'))->download($result->path, $result->filename);
    }

    /** Semua hasil dalam satu ZIP. File dibaca dari disk saat ZIP ditulis, bukan ditahan di memori. */
    public function zip(Booking $booking): BinaryFileResponse
    {
        $this->ensurePaid($booking);
        $disk = Storage::disk(config('aperta.photo_disk'));
        $file = tempnam(sys_get_temp_dir(), 'aperta');
        $temps = [];
        $names = [];
        $zip = new ZipArchive();
        $zip->open($file, ZipArchive::OVERWRITE);

        foreach ($booking->results()->orderBy('id')->get() as $result) {
            $name = self::uniqueName($result->filename, $names);
            $zip->addFile($this->localPath($disk, $result->path, $temps), $name);
            $zip->setCompressionName($name, ZipArchive::CM_STORE); // JPEG sudah terkompresi
        }

        $zip->close();
        array_map('unlink', $temps);

        return response()->download($file, Str::slug("hasil-{$booking->code}") . '.zip')->deleteFileAfterSend();
    }

    /** Disk lokal dibaca langsung; disk cloud (S3/R2) disalin dulu ke file sementara. */
    private function localPath($disk, string $path, array &$temps): string
    {
        if (config('filesystems.disks.' . config('aperta.photo_disk') . '.driver') === 'local') {
            return $disk->path($path);
        }

        file_put_contents($temps[] = tempnam(sys_get_temp_dir(), 'aperta'), $disk->readStream($path));

        return end($temps);
    }

    /** DSC_1.jpg, DSC_1-2.jpg, DSC_1-3.jpg, ... */
    private static function uniqueName(string $name, array &$seen): string
    {
        $candidate = $name;
        for ($i = 2; array_key_exists($candidate, $seen); $i++) {
            $candidate = pathinfo($name, PATHINFO_FILENAME) . "-{$i}." . pathinfo($name, PATHINFO_EXTENSION);
        }
        $seen[$candidate] = true;

        return $candidate;
    }

    private function ensurePaid(Booking $booking): void
    {
        abort_unless($booking->canDownloadResults(), 403, 'Hasil bisa diunduh setelah tagihan lunas.');
    }
}
