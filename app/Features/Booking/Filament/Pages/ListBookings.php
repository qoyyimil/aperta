<?php

namespace App\Features\Booking\Filament\Pages;

use App\Features\Booking\Filament\BookingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBookings extends ListRecords
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
