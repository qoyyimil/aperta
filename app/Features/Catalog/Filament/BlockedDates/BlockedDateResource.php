<?php

namespace App\Features\Catalog\Filament\BlockedDates;

use App\Filament\Field;
use App\Filament\SimpleResource;
use App\Models\BlockedDate;
use BackedEnum;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/** Tanggal yang ditutup untuk booking online (BOOK-4, PKG-3). */
class BlockedDateResource extends SimpleResource
{
    protected static ?string $model = BlockedDate::class;
    protected static ?string $modelLabel = 'tanggal libur';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;
    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';
    protected static string $managePage = ManageBlockedDates::class;
    protected static ?string $sortBy = 'date';
    protected static bool $newestFirst = true;

    protected static function fields(): array
    {
        return [
            Field::date('date', 'Tanggal')->required()->unique()->sortable(),
            Field::text('reason', 'Keterangan')->hint('Mis. libur nasional'),
        ];
    }
}
