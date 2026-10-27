<?php

namespace App\Features\Catalog\Filament\Addons;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAddons extends ManageRecords
{
    protected static string $resource = AddonResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
