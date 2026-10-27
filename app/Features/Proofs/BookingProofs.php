<?php

namespace App\Features\Proofs;

use App\Enums\BookingStatus;
use App\Models\Booking;

/**
 * Bagian Booking untuk proof: status unggahan dan publikasi galeri (PRF).
 *
 * @mixin Booking
 */
trait BookingProofs
{
    public function hasPendingProofs(): bool
    {
        return $this->proofs()->whereNull('preview_path')->exists();
    }

    /** Buka galeri untuk klien dengan kuota & batas waktu final (PRF-6, PRF-7). */
    public function publishProofs(int $quota, int $extraPrice, string $deadline): void
    {
        $this->forceFill([
            'edit_quota' => $quota,
            'extra_photo_price' => $extraPrice,
            'selection_deadline' => $deadline,
        ]);
        $this->transitionTo(BookingStatus::Selecting, "Kuota {$quota} foto, batas pilih {$deadline}");
    }
}
