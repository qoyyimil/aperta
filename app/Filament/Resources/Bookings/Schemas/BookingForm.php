<?php

namespace App\Filament\Resources\Bookings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class BookingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->columns(1)->components([
            Section::make('Klien & paket')->columns(2)->visibleOn('create')->schema([
                Select::make('client_id')
                    ->label('Klien')
                    ->relationship('client', 'name')
                    ->searchable(['name', 'whatsapp'])
                    ->preload()
                    ->createOptionForm(self::clientFields())
                    ->required(),
                Select::make('package_id')
                    ->label('Paket')
                    ->relationship('package', 'name', fn (Builder $query) => $query->where('is_active', true))                    ->helperText('Harga dan kuota disalin saat booking dibuat, tidak bisa diubah setelahnya.')
                    ->required(),
            ]),
            Section::make('Jadwal & lokasi')->columns(3)->schema([
                DatePicker::make('session_date')->label('Tanggal sesi')->required(),
                TimePicker::make('starts_at')->label('Mulai')->seconds(false)->required(),
                TimePicker::make('ends_at')->label('Selesai')->seconds(false)->after('starts_at')->required(),
                TextInput::make('city')->label('Kota')->required(),
                TextInput::make('location')->label('Lokasi'),
                TextInput::make('institution')->label('Kampus / instansi'),
                Textarea::make('notes')->label('Catatan')->columnSpanFull(),
            ]),
        ]);
    }

    private static function clientFields(): array
    {
        return [
            TextInput::make('name')->label('Nama')->required(),
            TextInput::make('email')->email()->required(),
            TextInput::make('whatsapp')->tel()->regex('/^(\+62|0)8\d{8,13}$/')->required(),
            TextInput::make('instagram'),
        ];
    }
}
