<?php

namespace App\Http\Presenters;

use App\Models\Booking;
use App\Models\ProofPhoto;

/** Data galeri pemilihan foto untuk klien (fitur SEL). */
final class GalleryView
{
    /** Tautan foto berlaku cukup lama untuk satu sesi memilih. */
    private const URL_MINUTES = 180;

    public static function of(Booking $b): array
    {
        $b->loadMissing('client');

        return [
            'booking' => [
                'code' => $b->code,
                'token' => $b->access_token,
                'name' => strtok($b->client->name, ' '),
                'open' => $b->status->selectionOpen(),
                'quota' => $b->edit_quota,
                'extraPrice' => $b->extra_photo_price,
                'deadline' => $b->selection_deadline?->translatedFormat('l, d F Y'),
                'overdue' => (bool) $b->selection_deadline?->endOfDay()->isPast(),
                'submittedAt' => $b->selection_submitted_at?->translatedFormat('d F Y, H:i'),
            ],
            'photos' => $b->proofs()->whereNotNull('preview_path')->orderBy('frame')->get()
                ->map(fn (ProofPhoto $p) => self::photo($p))->values(),
        ];
    }

    public static function photo(ProofPhoto $p): array
    {
        return [
            'id' => $p->id,
            'frame' => $p->frame,
            'filename' => $p->filename,
            'thumb' => $p->url('thumb', self::URL_MINUTES),
            'preview' => $p->url('preview', self::URL_MINUTES),
            'selected' => $p->is_selected,
            'note' => $p->note,
        ];
    }
}
