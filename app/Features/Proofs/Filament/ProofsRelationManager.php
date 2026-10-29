<?php

namespace App\Features\Proofs\Filament;

use App\Enums\BookingStatus;
use App\Models\ProofPhoto;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

/** Daftar foto proof beserta status proses dan pilihan klien. */
class ProofsRelationManager extends RelationManager
{
    protected static string $relationship = 'proofs';
    protected static ?string $title = 'Foto proof';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        // ProofPhoto tanpa policy, jadi hak hapus dicek di sini.
        $editable = fn() => (
            $this->getOwnerRecord()->status === BookingStatus::Shot
            && auth()->user()->can('manageProofs', $this->getOwnerRecord())
        );

        return $table
            ->columns([
                ImageColumn::make('thumb')
                    ->label('')
                    ->state(fn(ProofPhoto $record) => $record->url())
                    ->imageHeight(56),
                TextColumn::make('frame')->label('Frame')->sortable(),
                TextColumn::make('filename')->label('Nama file')->searchable(),
                TextColumn::make('state')
                    ->label('Status')
                    ->badge()
                    ->state(fn(ProofPhoto $record) => match (true) {
                        filled($record->error) => 'Gagal',
                        $record->isReady() => 'Siap',
                        default => 'Memproses',
                    })
                    ->color(fn(string $state) => ['Gagal' => 'danger', 'Siap' => 'success'][$state] ?? 'warning')
                    ->tooltip(fn(ProofPhoto $record) => $record->error),
                IconColumn::make('is_selected')
                    ->label('Dipilih klien')
                    ->boolean()
                    ->visible(fn() => !$editable()),
                TextColumn::make('note')
                    ->label('Catatan klien')
                    ->wrap()
                    ->placeholder('-')
                    ->visible(fn() => !$editable()),
            ])
            ->defaultSort('frame')
            ->paginated([50, 100, 'all'])
            // Segarkan otomatis selama masih ada foto yang diproses (yang gagal tidak ditunggu).
            ->poll(fn() => $this->getOwnerRecord()->proofs()->whereNull('preview_path')->whereNull('error')->exists()
                ? '5s'
                : null)
            ->emptyStateHeading('Belum ada foto proof')
            ->emptyStateDescription('Gunakan tombol "Unggah proof" setelah sesi foto selesai.')
            ->recordActions([
                DeleteAction::make()
                    ->visible($editable)
                    ->using(fn(ProofPhoto $record) => $record->discard()),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()
                    ->visible($editable)
                    ->using(fn(Collection $records) => $records->each->discard()),
            ]);
    }
}
