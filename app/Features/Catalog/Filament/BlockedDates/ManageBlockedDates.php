<?php

namespace App\Features\Catalog\Filament\BlockedDates;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageBlockedDates extends ManageRecords
{
    protected static string $resource = BlockedDateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
