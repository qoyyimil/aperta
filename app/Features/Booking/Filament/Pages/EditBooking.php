<?php

namespace App\Features\Booking\Filament\Pages;

use App\Features\Booking\Filament\BookingResource;
use Filament\Resources\Pages\EditRecord;

class EditBooking extends EditRecord
{
    use GuardsSchedule;

    protected static string $resource = BookingResource::class;
    protected static ?string $title = 'Ubah booking';

    /** Pembayaran, proof, dan riwayat dikelola di halaman detail, bukan di sini. */
    public function getRelationManagers(): array
    {
        return [];
    }

    protected function beforeSave(): void
    {
        $this->guardSchedule($this->record->id);
    }
}
