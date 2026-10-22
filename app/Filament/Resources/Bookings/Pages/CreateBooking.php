<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Package;
use Filament\Resources\Pages\CreateRecord;

class CreateBooking extends CreateRecord
{
    use GuardsSchedule;
    protected static string $resource = BookingResource::class;

    protected function beforeCreate(): void
    {
        $this->guardSchedule();
    }
    
    /** Booking dari dashboard (mis. lewat chat WA) juga menyalin paket (BR-2). */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return Package::findOrFail($data['package_id'])->snapshot() + $data;
    }
}
