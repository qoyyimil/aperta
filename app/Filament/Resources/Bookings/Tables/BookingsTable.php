<?php

namespace App\Filament\Resources\Bookings\Tables;

use App\Enums\BookingStatus;
use App\Filament\Resources\Bookings\Actions\StatusActions;
use App\Models\Booking;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')->label('Kode')->fontFamily(FontFamily::Mono)->searchable()->copyable(),
                TextColumn::make('client.name')->label('Klien')->searchable(),
                TextColumn::make('client.whatsapp')
                    ->label('WhatsApp')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('service')->label('Layanan'),
                TextColumn::make('session_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable()
                    ->description(fn(Booking $record) => substr($record->starts_at, 0, 5)),
                TextColumn::make('package_name')->label('Paket')->toggleable(),
                TextColumn::make('status')->badge(),
            ])
            ->defaultSort('session_date', 'desc')
            ->filters([
                SelectFilter::make('status')->options(BookingStatus::class)->multiple(),
                SelectFilter::make('crew')
                    ->label('Tim')
                    ->relationship('crew', 'name')
                    ->visible(fn() => auth()->user()->isAdmin()),
                self::periodFilter(),
            ])
            ->recordActions([
                ViewAction::make(),
                ActionGroup::make(StatusActions::all()),
            ]);
    }

    private static function periodFilter(): Filter
    {
        return Filter::make('period')
            ->schema([
                DatePicker::make('from')->label('Dari tanggal'),
                DatePicker::make('until')->label('Sampai tanggal'),
            ])
            ->query(fn(Builder $query, array $data) => $query->when($data['from'], fn($q, $d) => $q->whereDate(
                'session_date',
                '>=',
                $d,
            ))->when($data['until'], fn($q, $d) => $q->whereDate('session_date', '<=', $d)));
    }
}
