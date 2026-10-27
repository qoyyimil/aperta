<?php

namespace App\Features\Delivery\Http;

use App\Models\Booking;
use App\Models\EditedPhoto;

/** Data galeri hasil edit untuk klien (DLV-7, DLV-8). */
final class ResultsView
{
    private const URL_MINUTES = 180;

    public static function of(Booking $b): array
    {
        $b->loadMissing('client');
        $canDownload = $b->canDownloadResults();

        return [
            'booking' => [
                'code' => $b->code,
                'token' => $b->access_token,
                'name' => strtok($b->client->name, ' '),
                'deliveredAt' => $b->delivered_at?->translatedFormat('d F Y'),
                'canDownload' => $canDownload,
                'remaining' => $canDownload ? 0 : $b->balance(),
            ],
            'results' => $b
                ->results()
                ->whereNotNull('preview_path')
                ->with('proof')
                ->orderBy('id')
                ->get()
                ->map(fn(EditedPhoto $r) => [
                    'id' => $r->id,
                    'filename' => $r->filename,
                    'thumb' => $r->url('thumb', self::URL_MINUTES),
                    'preview' => $r->url('preview', self::URL_MINUTES),
                    'before' => $r->proof?->url('preview', self::URL_MINUTES),
                    'frame' => $r->proof?->frame,
                    'note' => $r->proof?->note,
                ])
                ->values(),
        ];
    }
}
