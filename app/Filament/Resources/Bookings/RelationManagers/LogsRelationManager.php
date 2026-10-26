<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use App\Enums\BookingStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/** Riwayat perubahan booking, hanya baca (MGT-3). */
class LogsRelationManager extends RelationManager
{
    protected static string $relationship = 'logs';
    protected static ?string $title = 'Riwayat';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        $status = fn(?string $s) => BookingStatus::tryFrom((string) $s)?->getLabel() ?? $s;

        return $table->columns([
            TextColumn::make('created_at')->label('Waktu')->dateTime('d M Y H:i'),
            TextColumn::make('user.name')->label('Oleh')->placeholder('Sistem / klien'),
            TextColumn::make('action')->label('Aksi'),
            TextColumn::make('from_status')->label('Dari')->formatStateUsing($status),
            TextColumn::make('to_status')->label('Ke')->formatStateUsing($status),
            TextColumn::make('reason')->label('Keterangan')->wrap(),
        ])->defaultSort('id', 'desc');
    }
}
