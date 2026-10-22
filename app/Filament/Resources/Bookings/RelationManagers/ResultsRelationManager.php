<?php

namespace App\Filament\Resources\Bookings\RelationManagers;

use App\Models\Booking;
use App\Models\EditedPhoto;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/** Hasil edit beserta pencocokannya dengan foto pilihan klien (DLV-2, DLV-3, DLV-4). */
class ResultsRelationManager extends RelationManager
{
    protected static string $relationship = 'results';
    protected static ?string $title = 'Hasil edit';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->status->hasResults();
    }

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->description(fn () => $this->progress())
            ->columns([
                ImageColumn::make('thumb')->label('')->state(fn (EditedPhoto $record) => $record->url())->imageHeight(56),
                TextColumn::make('filename')->label('Nama file')->searchable(),
                TextColumn::make('proof.frame')->label('Untuk foto')->badge()
                    ->formatStateUsing(fn ($state) => 'Frame ' . str_pad($state, 2, '0', STR_PAD_LEFT))
                    ->placeholder('Tambahan'),
                TextColumn::make('size')->label('Ukuran')->formatStateUsing(fn (int $state) => number_format($state / 1048576, 1, ',', '.') . ' MB'),
                TextColumn::make('state')->label('Status')->badge()
                    ->state(fn (EditedPhoto $record) => match (true) {
                        $record->failed() => 'Gagal',
                        $record->isReady() => 'Siap',
                        default => 'Memproses',
                    })
                    ->color(fn (string $state) => ['Gagal' => 'danger', 'Siap' => 'success'][$state] ?? 'warning')
                    ->tooltip(fn (EditedPhoto $record) => $record->error),
            ])
            ->defaultSort('id')
            ->paginated([50, 100, 'all'])
            ->poll(fn () => $this->getOwnerRecord()->results()->whereNull('preview_path')->whereNull('error')->exists() ? '5s' : null)
            ->emptyStateHeading('Belum ada hasil edit')
            ->emptyStateDescription('Gunakan tombol "Unggah hasil edit" di atas.')
            ->recordActions([self::linkAction(), DeleteAction::make()->using(fn (EditedPhoto $record) => $record->discard())]);
    }

    /** "18 dari 25 foto pilihan sudah ada hasil. Belum: DSC_4512, ..." */
    private function progress(): string
    {
        /** @var Booking $booking */
        $booking = $this->getOwnerRecord();
        $selected = $booking->selected()->count();
        $missing = $booking->missingResults()->orderBy('frame')->pluck('filename');
        $text = ($selected - $missing->count()) . " dari {$selected} foto pilihan sudah ada hasil.";

        return $missing->isEmpty() ? $text : $text . ' Belum: ' . $missing->map(fn ($f) => pathinfo($f, PATHINFO_FILENAME))->join(', ');
    }

    /** Hubungkan manual jika nama file hasil tidak cocok otomatis. */
    private static function linkAction(): Action
    {
        return Action::make('link')
            ->label('Hubungkan')
            ->icon(Heroicon::OutlinedLink)
            ->color('gray')
            ->fillForm(fn (EditedPhoto $record) => ['proof_photo_id' => $record->proof_photo_id])
            ->schema([
                Select::make('proof_photo_id')->label('Foto pilihan klien')->searchable()
                    ->options(fn (EditedPhoto $record) => $record->booking->selected()->orderBy('frame')->get()
                        ->mapWithKeys(fn ($p) => [$p->id => "Frame {$p->frame} · {$p->filename}"]))
                    ->placeholder('Tambahan (tidak terhubung)'),
            ])
            ->action(fn (EditedPhoto $record, array $data) => $record->update(['proof_photo_id' => $data['proof_photo_id']]));
    }
}
