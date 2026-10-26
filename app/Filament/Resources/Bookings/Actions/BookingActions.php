<?php

namespace App\Filament\Resources\Bookings\Actions;

use App\Actions\AddProofs;
use App\Actions\AddResults;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

/** Aksi kerja di halaman detail booking, masing-masing dengan modal sendiri (bukan lewat form Ubah). */
final class BookingActions
{
    /** Menugaskan tim (MGT-4). */
    public static function crew(): Action
    {
        return Action::make('crew')
            ->label('Atur tim')
            ->icon(Heroicon::OutlinedUserGroup)
            ->color('gray')
            ->visible(fn() => auth()->user()->isAdmin())
            ->fillForm(fn(Booking $record) => ['crew' => $record->crew->modelKeys()])
            ->schema([
                Select::make('crew')
                    ->label('Fotografer / editor')
                    ->multiple()
                    ->preload()
                    ->options(
                        fn() => User::where('is_active', true)
                            ->where('role', '!=', UserRole::Admin)
                            ->pluck('name', 'id'),
                    ),
            ])
            ->action(function (Booking $record, array $data) {
                $record->assignCrew($data['crew']);
                Notification::make()->title('Tim diperbarui')->success()->send();
            });
    }

    /** Unggah proof setelah sesi (PRF-1, PRF-2). */
    public static function uploadProofs(): Action
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
    public static function publishProofs(): Action
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

    /** Unggah hasil edit; bisa ditambah lagi setelah dikirim (DLV-1, DLV-10). */
    public static function uploadResults(): Action
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
    public static function deliverResults(): Action
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
