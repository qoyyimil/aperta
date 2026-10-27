<?php

namespace App\Features\Catalog\Filament\Vouchers;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageVouchers extends ManageRecords
{
    protected static string $resource = VoucherResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
