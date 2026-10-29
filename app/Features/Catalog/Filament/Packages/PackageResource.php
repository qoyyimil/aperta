<?php

namespace App\Features\Catalog\Filament\Packages;

use App\Filament\Field;
use App\Filament\SimpleResource;
use App\Models\Package;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

class PackageResource extends SimpleResource
{
    protected static ?string $model = Package::class;
    protected static ?string $modelLabel = 'paket';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;
    protected static string $managePage = ManagePackages::class;
    protected static ?string $sortBy = 'service';
    protected static int $formColumns = 2;

    protected static function fields(): array
    {
        return [
            Field::text('service', 'Layanan')->required()->suggestions(['Wisuda', 'Couple', 'Event', 'Studio']),
            Field::text('name', 'Nama paket')->required()->searchable(),
            Field::number('duration_minutes', 'Durasi')->required()->min(15)->unit('menit'),
            Field::money('price', 'Harga')->required()->sortable(),
            Field::number('edit_quota', 'Kuota edit')->required()->min(0)->unit('foto'),
            Field::money('extra_photo_price', 'Harga foto tambahan')->required(),
            Field::active(),
        ];
    }
}
