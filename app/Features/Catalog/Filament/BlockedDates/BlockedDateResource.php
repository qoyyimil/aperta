<?php

namespace App\Features\Catalog\Filament\BlockedDates;

use App\Models\BlockedDate;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Tanggal yang ditutup untuk booking online (BOOK-4, PKG-3). */
class BlockedDateResource extends Resource
{
    protected static ?string $model = BlockedDate::class;
    protected static ?string $modelLabel = 'tanggal libur';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedNoSymbol;
    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            DatePicker::make('date')->label('Tanggal')->required()->unique(ignoreRecord: true),
            TextInput::make('reason')->label('Keterangan')->placeholder('Mis. libur nasional'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')->label('Tanggal')->date('l, d M Y')->sortable(),
                TextColumn::make('reason')->label('Keterangan')->placeholder('-'),
            ])
            ->defaultSort('date', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageBlockedDates::route('/')];
    }
}
