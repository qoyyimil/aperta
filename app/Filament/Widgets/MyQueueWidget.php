<?php

namespace App\Filament\Widgets;

use App\Enums\UserRole;
use App\Filament\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Support\Enums\FontFamily;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** "Apa yang harus saya kerjakan hari ini?", isinya sesuai peran. */
class MyQueueWidget extends TableWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->heading(match ($user->role) {
                UserRole::Admin => 'Perlu tindakan',
                UserRole::Photographer => 'Belum diunggah',
                UserRole::Editor => 'Antrean edit',
            })
            ->query(Booking::queueFor($user))
            ->columns([
                TextColumn::make('code')->label('Kode')->fontFamily(FontFamily::Mono),
                TextColumn::make('client.name')->label('Klien'),
                TextColumn::make('session_date')
                    ->label('Jadwal')
                    ->date('d M Y')
                    ->description(fn(Booking $record) => substr($record->starts_at, 0, 5)),
                TextColumn::make('status')->badge(),
                TextColumn::make('reason')
                    ->label('Alasan')
                    ->state(fn(Booking $record) => $record->actionReason()),
            ])
            ->recordUrl(fn(Booking $record) => BookingResource::getUrl('view', ['record' => $record]))
            ->emptyStateIcon(Heroicon::OutlinedCheckCircle)
            ->emptyStateHeading('Tidak ada yang perlu ditindaklanjuti')
            ->emptyStateDescription('Semua beres untuk saat ini.');
    }
}
