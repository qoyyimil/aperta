<?php

namespace App\Features\Catalog\Filament\Vouchers;

use App\Enums\DiscountType;
use App\Models\Voucher;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;
    protected static ?string $modelLabel = 'voucher';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;
    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    public static function form(Schema $schema): Schema
    {
        $isPercent = fn(Get $get) => in_array($get('type'), [DiscountType::Percent, 'percent'], true);

        return $schema
            ->columns(2)
            ->components([
                TextInput::make('code')
                    ->label('Kode')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->dehydrateStateUsing(fn(string $state) => strtoupper(trim($state))),
                Select::make('type')->label('Jenis potongan')->options(DiscountType::class)->required()->live(),
                TextInput::make('value')
                    ->label('Nilai')
                    ->numeric()
                    ->minValue(1)
                    ->required()
                    ->maxValue(fn(Get $get) => $isPercent($get) ? 100 : null)
                    ->prefix(fn(Get $get) => $isPercent($get) ? null : 'Rp')
                    ->suffix(fn(Get $get) => $isPercent($get) ? '%' : null),
                TextInput::make('quota')
                    ->label('Kuota pemakaian')
                    ->numeric()
                    ->minValue(1)
                    ->helperText('Kosongkan jika tanpa batas.'),
                DatePicker::make('starts_on')->label('Berlaku dari'),
                DatePicker::make('ends_on')->label('Berlaku sampai')->afterOrEqual('starts_on'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->label('Kode')->searchable()->copyable(),
            TextColumn::make('value')
                ->label('Potongan')
                ->formatStateUsing(fn(Voucher $record) => $record->type === DiscountType::Percent
                    ? "{$record->value}%"
                    : 'Rp' . number_format($record->value, 0, ',', '.')),
            TextColumn::make('ends_on')->label('Sampai')->date('d M Y')->placeholder('Tanpa batas'),
            TextColumn::make('used')
                ->label('Dipakai')
                ->formatStateUsing(fn(Voucher $record) => $record->used . ' / ' . ($record->quota ?? '∞')),
        ])->recordActions([EditAction::make(), DeleteAction::make()]);
    }

    public static function getPages(): array
    {
        return ['index' => ManageVouchers::route('/')];
    }
}
