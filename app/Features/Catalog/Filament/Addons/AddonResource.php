<?php

namespace App\Features\Catalog\Filament\Addons;

use App\Filament\Field;
use App\Filament\SimpleResource;
use App\Models\Addon;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class AddonResource extends SimpleResource
{
    protected static ?string $model = Addon::class;
    protected static ?string $modelLabel = 'add-on';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;
    protected static string $managePage = ManageAddons::class;
    protected static ?string $sortBy = 'name';

    protected static function fields(): array
    {
        return [
            Field::text('name', 'Nama')->required()->searchable(),
            Field::money('price', 'Harga')->required()->sortable(),
            Field::number('max_qty', 'Maks. per booking')->required()->min(1)->default(10),
            Field::active(),
        ];
    }
}
