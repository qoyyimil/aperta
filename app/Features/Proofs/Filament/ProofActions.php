<?php

namespace App\Features\Proofs\Filament;

use App\Enums\BookingStatus;
use App\Features\Proofs\AddProofs;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/** Aksi proof di halaman detail booking, masing-masing dengan modal sendiri. */
final class ProofActions
{
    /** Unggah proof setelah sesi (PRF-1, PRF-2). */
    public static function upload(): Action
    {
        return Action::make('uploadProofs')
            ->label('Unggah proof')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->visible(
                fn(Booking $record) => (
                    $record->status === BookingStatus::Shot
                    && auth()->user()->can('manageProofs', $record)
                ),
            )
            ->modalDescription(
                'Pilih foto hasil sortir (JPG/PNG, maks. 40 MB per file). Foto diproses otomatis menjadi thumbnail dan preview berwatermark.',
            )
            ->schema([
                FileUpload::make('files')
                    ->label('Foto')
                    ->multiple()
                    ->required()
                    ->acceptedFileTypes(['image/jpeg', 'image/png'])
                    ->maxSize(40 * 1024)
                    ->maxFiles(300)
                    ->maxParallelUploads(4)
                    ->disk(config('aperta.photo_disk'))
                    ->visibility('private')
                    ->panelLayout('grid')
                    // Nama asli dipertahankan; folder acak per file mencegah file saling menimpa.
                    ->directory(fn() => 'proof-uploads/' . Str::ulid())
                    ->preserveFilenames(),
            ])
            ->action(function (Booking $record, array $data, AddProofs $add) {
                ['added' => $added, 'skipped' => $skipped] = $add->handle($record, $data['files']);

                Notification::make()
                    ->success()
                    ->title("{$added} foto ditambahkan dan sedang diproses")
                    ->body($skipped ? 'Dilewati karena namanya sudah ada: ' . implode(', ', $skipped) : null)
                    ->send();
            });
    }

    /** Buka galeri untuk klien (PRF-6, PRF-7). */
    public static function publish(): Action
    {
        return Action::make('publishProofs')
            ->label('Publikasikan ke klien')
            ->icon(Heroicon::OutlinedPaperAirplane)
            ->color('success')
            ->visible(
                fn(Booking $record) => (
                    $record->status === BookingStatus::Shot
                    && auth()->user()->can('manageProofs', $record)
                ),
            )
            ->disabled(fn(Booking $record) => $record->proofs()->doesntExist() || $record->hasPendingProofs())
            ->tooltip(fn(Booking $record) => $record->hasPendingProofs()
                ? 'Tunggu semua foto selesai diproses, atau hapus foto yang gagal.'
                : null)
            ->modalDescription('Setelah dipublikasikan, klien bisa memilih foto lewat halaman lacak.')
            ->fillForm(fn(Booking $record) => [
                'quota' => $record->edit_quota,
                'extra_price' => $record->extra_photo_price,
                'deadline' => today()->addDays(config('aperta.selection_days'))->toDateString(),
            ])
            ->schema([
                TextInput::make('quota')
                    ->label('Kuota edit')
                    ->numeric()
                    ->minValue(1)
                    ->suffix('foto')
                    ->required(),
                TextInput::make('extra_price')
                    ->label('Harga foto tambahan')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('Rp')
                    ->required(),
                DatePicker::make('deadline')->label('Batas waktu pemilihan')->minDate(today())->required(),
            ])
            ->action(function (Booking $record, array $data) {
                $record->publishProofs((int) $data['quota'], (int) $data['extra_price'], $data['deadline']);
                Notification::make()->title('Galeri dibuka untuk klien')->success()->send();
            });
    }
}
