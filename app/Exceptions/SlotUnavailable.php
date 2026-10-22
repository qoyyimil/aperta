<?php

namespace App\Exceptions;

use DomainException;
use Illuminate\Http\RedirectResponse;

/** Slot sudah penuh atau tanggal ditutup saat booking dikirim (BOOK-8). */
class SlotUnavailable extends DomainException
{
    public function render(): RedirectResponse
    {
        return back()->withInput()->withErrors([
            'starts_at' => 'Jadwal ini baru saja penuh atau tidak tersedia. Silakan pilih jam atau tanggal lain.',
        ]);
    }
}
