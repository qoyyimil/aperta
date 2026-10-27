<?php

namespace App\Features\Booking\Filament\Pages;

use App\Features\Booking\Filament\BookingResource;
use App\Features\Booking\Filament\StatusActions;
use App\Features\Delivery\Filament\DeliveryActions;
use App\Features\Proofs\Filament\ProofActions;
use App\Features\Team\Filament\CrewAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;

class ViewBooking extends ViewRecord
{
    protected static string $resource = BookingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ProofActions::upload(),
            ProofActions::publish(),
            DeliveryActions::upload(),
            DeliveryActions::deliver(),
            CrewAction::make(),
            ...StatusActions::all(),
            EditAction::make()->label('Ubah jadwal')->icon(Heroicon::OutlinedCalendarDays),
        ];
    }
}
