<?php

namespace App\Actions;

use App\Jobs\RenderPhoto;
use App\Models\Booking;
use Illuminate\Support\Facades\Storage;

/** Mendaftarkan file proof yang baru diunggah lalu memprosesnya di antrean (PRF-1, PRF-4). */
final class AddProofs
{
    /**
     * @param  string[]  $paths  file tersimpan; nama file = nama asli dari kamera
     * @return array{added: int, skipped: string[]}
     */
    public function handle(Booking $booking, array $paths): array
    {
        $taken = $booking
            ->proofs()
            ->withTrashed()
            ->pluck('filename')
            ->map(fn($n) => strtolower($n))
            ->flip();
        $frame = (int) $booking->proofs()->withTrashed()->max('frame');
        $added = 0;
        $skipped = [];

        // Urutan frame mengikuti nama file kamera: DSC_0009 sebelum DSC_0010.
        usort($paths, fn($a, $b) => strnatcasecmp(basename($a), basename($b)));

        foreach ($paths as $path) {
            $name = basename($path);
            if (isset($taken[strtolower($name)])) {
                Storage::disk(config('aperta.photo_disk'))->delete($path);
                $skipped[] = $name;

                continue;
            }

            $taken[strtolower($name)] = true;
            $proof = $booking->proofs()->create(['frame' => ++$frame, 'filename' => $name, 'source_path' => $path]);
            RenderPhoto::dispatch($proof);
            $added++;
        }

        return compact('added', 'skipped');
    }
}
