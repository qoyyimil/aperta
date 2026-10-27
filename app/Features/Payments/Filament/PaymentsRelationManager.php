<?php

namespace App\Features\Payments\Filament;

use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Pencatatan pembayaran manual oleh admin (PAY-1). */
class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';
    protected static ?string $title = 'Pembayaran';

    private const METHODS = ['transfer' => 'Transfer bank', 'qris' => 'QRIS', 'cash' => 'Tunai'];

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('amount')
                ->label('Jumlah')
                ->numeric()
                ->minValue(1)
                ->prefix('Rp')
                ->required(),
            DatePicker::make('paid_on')->label('Tanggal bayar')->default(now())->required(),
            Select::make('method')->label('Metode')->options(self::METHODS)->required(),
            FileUpload::make('proof_path')
                ->label('Bukti transfer')
                ->image()
                ->disk('local')
                ->directory('payment-proofs')
                ->visibility('private'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('paid_on')->label('Tanggal')->date('d M Y'),
                TextColumn::make('amount')->label('Jumlah')->money('IDR', locale: 'id'),
                TextColumn::make('method')
                    ->label('Metode')
                    ->formatStateUsing(fn(string $state) => self::METHODS[$state] ?? $state),
                TextColumn::make('recorder.name')->label('Dicatat oleh')->placeholder('-'),
            ])
            ->defaultSort('paid_on')
            ->headerActions([CreateAction::make()->label('Catat pembayaran')])
            ->recordActions([DeleteAction::make()]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
