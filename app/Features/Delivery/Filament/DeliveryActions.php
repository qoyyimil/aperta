<?php

namespace App\Features\Delivery\Filament;

use App\Enums\BookingStatus;
use App\Features\Delivery\AddResults;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/** Aksi hasil edit di halaman detail booking. */
final class DeliveryActions
{
    /** Unggah hasil edit; bisa ditambah lagi setelah dikirim (DLV-1, DLV-10). */
    public static function upload(): Action
    {
        return Action::make('uploadResults')
            ->label('Unggah hasil edit')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->visible(
                fn(Booking $record) => $record->status->hasResults() && auth()->user()->can('manageResults', $record),
            )
            ->modalDescription(
                'JPG/PNG hasil export, maks. 40 MB per file. Nama file yang memuat nama foto asli (mis. DSC_4512_edit.jpg) otomatis dicocokkan dengan pilihan klien.',
            )
            ->schema([
                FileUpload::make('files')
                    ->label('Hasil edit')
                    ->multiple()
                    ->required()
                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                    ->maxSize(40 * 1024)
                    ->maxFiles(300)
                    ->maxParallelUploads(4)
                    ->disk(config('aperta.photo_disk'))
                    ->visibility('private')
                    ->panelLayout('grid')
                    ->directory(fn(Booking $record) => "results/{$record->id}/" . Str::ulid())
                    ->preserveFilenames(),
            ])
            ->action(function (Booking $record, array $data, AddResults $add) {
                ['added' => $added, 'matched' => $matched] = $add->handle($record, $data['files']);
                $unmatched = $added - $matched;

                Notification::make()
                    ->success()
                    ->title("{$added} hasil edit diunggah")
                    ->body(
                        $unmatched
                            ? "{$unmatched} file tidak cocok dengan foto pilihan. Hubungkan manual di tab Hasil edit."
                            : null,
                    )
                    ->send();
            });
    }

    /** Kirim hasil ke klien (DLV-5, DLV-6). */
    public static function deliver(): Action
    {
        return Action::make('deliverResults')
            ->label('Kirim hasil ke klien')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('success')
            ->visible(
                fn(Booking $record) => (
                    $record->status === BookingStatus::Editing
                    && auth()->user()->can('manageResults', $record)
                ),
            )
            ->disabled(fn(Booking $record) => $record->results()->doesntExist() || $record->hasPendingResults())
            ->tooltip(fn(Booking $record) => $record->hasPendingResults()
                ? 'Tunggu semua hasil selesai diproses, atau hapus yang gagal.'
                : null)
            ->requiresConfirmation()
            ->modalDescription(function (Booking $record) {
                $missing = $record->missingResults()->count();

                return $missing
                    ? "Masih ada {$missing} foto pilihan klien yang belum punya hasil edit. Hasil tetap bisa ditambahkan setelah dikirim."
                    : 'Semua foto pilihan sudah punya hasil edit. Klien akan bisa melihat dan mengunduhnya setelah lunas.';
            })
            ->action(function (Booking $record) {
                $record->deliverResults();
                Notification::make()->title('Hasil edit terkirim ke klien')->success()->send();
            });
    }
}
