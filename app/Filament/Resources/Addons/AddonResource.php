<?php

namespace App\Filament\Resources\Addons;

use App\Filament\Resources\Addons\Pages\ManageAddons;
use App\Models\Addon;
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

class AddonResource extends Resource
{
    protected static ?string $model = Addon::class;
    protected static ?string $modelLabel = 'add-on';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;
    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('Nama')->required(),
            TextInput::make('price')->label('Harga')->numeric()->minValue(0)->prefix('Rp')->required(),
            TextInput::make('max_qty')->label('Jumlah maksimal per booking')->numeric()->minValue(1)->default(10)->required(),
            Toggle::make('is_active')->label('Aktif')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Add-on')->searchable(),
                TextColumn::make('price')->label('Harga')->money('IDR', locale: 'id')->sortable(),
                TextColumn::make('max_qty')->label('Maks.'),
                ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->defaultSort('name')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageAddons::route('/')];
    }
}
