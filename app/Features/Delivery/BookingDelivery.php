<?php

namespace App\Features\Delivery;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use DomainException;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bagian Booking untuk hasil edit dan unduhan (DLV).
 *
 * @mixin Booking
 */
trait BookingDelivery
{
    /** Foto pilihan yang belum punya hasil edit (DLV-3). */
    public function missingResults(): HasMany
    {
        return $this->selected()->whereDoesntHave('results');
    }

    public function hasPendingResults(): bool
    {
        return $this->results()->whereNull('preview_path')->exists();
    }

    /** Kirim hasil ke klien (DLV-6). */
    public function deliverResults(): void
    {
        throw_if(
            $this->results()->doesntExist(),
            new DomainException('Unggah minimal satu hasil edit sebelum mengirim.'),
        );

        $this->forceFill(['delivered_at' => now()]);
        $this->transitionTo(BookingStatus::Delivered, $this->results()->count() . ' hasil edit dikirim');
    }

    /** Unduhan resolusi penuh hanya setelah lunas (BR-6). */
    public function canDownloadResults(): bool
    {
        return $this->status === BookingStatus::Delivered && $this->paymentStatus() === PaymentStatus::Paid;
    }
}
