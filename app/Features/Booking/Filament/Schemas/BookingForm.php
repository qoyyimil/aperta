<?php

namespace App\Features\Booking\Filament\Schemas;

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
        return $schema
            ->columns(1)
            ->components([
                Section::make(fn(string $operation) => $operation === 'create' ? 'Klien & paket' : 'Klien')
                    ->columns(2)
                    ->schema([
                        Select::make('client_id')
                            ->label('Klien')
                            ->relationship('client', 'name')
                            ->searchable(['name', 'whatsapp'])
                            ->preload()
                            ->createOptionForm(self::clientFields())
                            ->editOptionForm(self::clientFields())
                            ->required(),
                        // Paket hanya dipilih saat dibuat, karena harganya disalin ke booking.
                        Select::make('package_id')
                            ->label('Paket')
                            ->relationship('package', 'name', fn(Builder $query) => $query->where('is_active', true))
                            ->helperText('Harga dan kuota disalin saat booking dibuat, tidak bisa diubah setelahnya.')
                            ->visibleOn('create')
                            ->required(),
                    ]),
                Section::make('Jadwal & lokasi')
                    ->columns(3)
                    ->schema([
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
            // Spasi dan tanda hubung boleh, seperti salinan dari WhatsApp; model menyimpannya sebagai 08xx.
            TextInput::make('whatsapp')->tel()->regex('/^(\+?62|0)\s?8[\d\s-]{8,16}$/')->required(),
            TextInput::make('instagram'),
        ];
    }
}
