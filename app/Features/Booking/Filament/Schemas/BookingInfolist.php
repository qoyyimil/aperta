<?php

namespace App\Features\Booking\Filament\Schemas;

use App\Models\Booking;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Booking')->schema([
                    TextEntry::make('code')->label('Kode')->copyable(),
                    TextEntry::make('status')->badge(),
                    TextEntry::make('payment')
                        ->label('Pembayaran')
                        ->badge()
                        ->state(fn(Booking $record) => $record->paymentStatus()),
                    TextEntry::make('cancel_reason')
                        ->label('Alasan batal')
                        ->visible(fn(Booking $record) => filled($record->cancel_reason)),
                ]),
                Section::make('Klien')->schema([
                    TextEntry::make('client.name')->label('Nama'),
                    TextEntry::make('client.whatsapp')->label('WhatsApp')->copyable(),
                    TextEntry::make('client.email')->label('Email')->copyable(),
                    TextEntry::make('client.instagram')->label('Instagram')->placeholder('-'),
                ]),
                Section::make('Jadwal')->schema([
                    TextEntry::make('session_date')->label('Tanggal')->date('l, d M Y'),
                    TextEntry::make('time')
                        ->label('Waktu')
                        ->state(
                            fn(Booking $record) => (
                                substr($record->starts_at, 0, 5) . '–' . substr($record->ends_at, 0, 5)
                            ),
                        ),
                    TextEntry::make('location')
                        ->label('Lokasi')
                        ->state(
                            fn(Booking $record) => collect([$record->location, $record->institution, $record->city])
                                ->filter()
                                ->join(', '),
                        ),
                    TextEntry::make('crew.name')->label('Tim')->badge()->placeholder('Belum ditugaskan'),
                ]),
                Section::make('Paket & tagihan')
                    ->columns(4)
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('package_name')->label('Paket'),
                        TextEntry::make('edit_quota')->label('Kuota edit')->suffix(' foto'),
                        self::rupiah('total', 'Total tagihan', fn(Booking $record) => $record->price()->total()),
                        self::rupiah('paid', 'Sudah dibayar', fn(Booking $record) => $record->paid()),
                    ]),
                TextEntry::make('due')
                    ->label('Lunasi sebelum')
                    ->state(fn(Booking $record) => $record->paymentDueOn()->translatedFormat('d M Y'))
                    ->color(fn(Booking $record) => $record->isPaymentOverdue() ? 'danger' : null)
                    ->helperText(fn(Booking $record) => $record->isPaymentOverdue()
                        ? 'Terlambat, tagihan sesi belum lunas'
                        : null)
                    ->visible(fn(Booking $record) => !$record->isSessionPaid()),
                TextEntry::make('notes')->label('Catatan')->placeholder('-')->columnSpanFull(),
            ]);
    }

    private static function rupiah(string $name, string $label, callable $state): TextEntry
    {
        return TextEntry::make($name)->label($label)->state($state)->money('IDR', locale: 'id');
    }
}
