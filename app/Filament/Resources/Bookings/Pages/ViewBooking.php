<?php

namespace App\Filament\Resources\Bookings\Pages;

use App\Filament\Resources\Bookings\Actions\StatusActions;
use App\Filament\Resources\Bookings\BookingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Bookings\Actions\BookingActions;
use Filament\Support\Icons\Heroicon;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
        BookingActions::uploadProofs(),
        BookingActions::publishProofs(),
        BookingActions::uploadResults(),
        BookingActions::deliverResults(),
        BookingActions::crew(),
        ...StatusActions::all(),
        EditAction::make()->label('Ubah jadwal')->icon(Heroicon::OutlinedCalendarDays),
    ];
    }
}
