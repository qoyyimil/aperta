<?php

namespace App\Features\Delivery;

use App\Jobs\RenderPhoto;
use App\Models\Booking;
use Illuminate\Support\Facades\Storage;

/** Mendaftarkan file hasil edit, mencocokkannya dengan foto pilihan, lalu membuat preview (DLV-1, DLV-2). */
final class AddResults
{
    /**
     * @param  string[]  $paths  file tersimpan; nama file = nama hasil export
     * @return array{added: int, matched: int}
     */
    public function handle(Booking $booking, array $paths): array
    {
        $disk = Storage::disk(config('aperta.photo_disk'));
        $selected = $booking->selected()->get();
        $matched = 0;

        foreach ($paths as $path) {
            $result = $booking
                ->results()
                ->make([
                    'filename' => basename($path),
                    'path' => $path,
                    'size' => $disk->size($path),
                    'uploaded_by' => auth()->id(),
                ])
                ->matchProof($selected);

            $result->save();
            RenderPhoto::dispatch($result);
            $matched += (int) filled($result->proof_photo_id);
        }

        if ($paths) {
            $booking->log('results', reason: count($paths) . ' file hasil diunggah');
        }

        return ['added' => count($paths), 'matched' => $matched];
    }
}
