<?php

namespace App\Features\Dashboard\Filament;

use App\Features\Booking\Filament\BookingResource;
use App\Models\Booking;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/** Sesi 7 hari ke depan yang bisa dilihat pengguna ini. */
class AgendaWidget extends TableWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Agenda 7 hari')
            ->query(Booking::upcoming(7)->visibleTo(auth()->user())->with('client', 'crew'))
            ->columns([
                TextColumn::make('session_date')
                    ->label('Jadwal')
                    ->date('D, d M')
                    ->description(
                        fn(Booking $record) => substr($record->starts_at, 0, 5) . '–' . substr($record->ends_at, 0, 5),
                    ),
                TextColumn::make('service')
                    ->label('Layanan')
                    ->description(fn(Booking $record) => $record->package_name),
                TextColumn::make('client.name')->label('Klien'),
                TextColumn::make('city')
                    ->label('Lokasi')
                    ->description(fn(Booking $record) => $record->location),
                TextColumn::make('crew.name')->label('Tim')->badge()->placeholder('Belum ada'),
            ])
            ->recordUrl(fn(Booking $record) => BookingResource::getUrl('view', ['record' => $record]))
            ->emptyStateIcon(Heroicon::OutlinedCalendar)
            ->emptyStateHeading('Belum ada sesi 7 hari ke depan');
    }
}
