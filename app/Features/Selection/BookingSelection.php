<?php

namespace App\Features\Selection;

use App\Enums\BookingStatus;
use App\Models\Booking;
use DomainException;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Bagian Booking untuk pilihan foto klien (SEL).
 *
 * @mixin Booking
 */
trait BookingSelection
{
    public function selected(): HasMany
    {
        return $this->proofs()->where('is_selected', true);
    }

    /** Klien mengunci pilihannya dan editing dimulai (SEL-8, SEL-9). */
    public function submitSelection(): void
    {
        throw_if($this->selected()->doesntExist(), new DomainException('Pilih minimal satu foto sebelum mengirim.'));

        $this->forceFill(['selection_submitted_at' => now()]);
        $this->transitionTo(BookingStatus::Editing, $this->selected()->count() . ' foto dipilih klien');
    }
}
