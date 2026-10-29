<?php

namespace App\Filament;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

/** Halaman tunggal SimpleResource dengan tombol Tambah di kanan atas. */
abstract class ManageSimpleRecords extends ManageRecords
{
    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
