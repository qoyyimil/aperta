<?php

namespace App\Filament\Resources\Packages;

use App\Filament\Resources\Packages\Pages\ManagePackages;
use App\Models\Package;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use UnitEnum;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;
    protected static ?string $modelLabel = 'paket';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;
    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(2)->components([
            TextInput::make('service')->label('Layanan')->datalist(['Wisuda', 'Couple', 'Event', 'Studio'])->required(),
            TextInput::make('name')->label('Nama paket')->required(),
            TextInput::make('duration_minutes')->label('Durasi')->numeric()->minValue(15)->suffix('menit')->required(),
            TextInput::make('price')->label('Harga')->numeric()->minValue(0)->prefix('Rp')->required(),
            TextInput::make('edit_quota')->label('Kuota edit')->numeric()->minValue(0)->suffix('foto')->required(),
            TextInput::make('extra_photo_price')->label('Harga foto tambahan')->numeric()->minValue(0)->prefix('Rp')->required(),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Paket')->description(fn (Package $record) => $record->service)->searchable(),
                TextColumn::make('duration_minutes')->label('Durasi')->suffix(' menit'),
                TextColumn::make('price')->label('Harga')->money('IDR', locale: 'id')->sortable(),
                TextColumn::make('edit_quota')->label('Kuota')->suffix(' foto'),
                TextColumn::make('extra_photo_price')->label('Foto tambahan')->money('IDR', locale: 'id'),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->defaultSort('service')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManagePackages::route('/')];
    }
}
