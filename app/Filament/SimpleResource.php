<?php

namespace App\Filament;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Menu satu halaman: tabel + tambah/ubah/hapus lewat modal, hanya untuk admin.
 * Menu baru cukup mengisi fields() dan $managePage. Contoh: Catalog/Filament/Addons.
 */
abstract class SimpleResource extends Resource
{
    protected static string|UnitEnum|null $navigationGroup = 'Katalog';

    /** @var class-string<ManageSimpleRecords> */
    protected static string $managePage;

    /** Kolom untuk mengurutkan tabel saat pertama dibuka. */
    protected static ?string $sortBy = null;

    protected static bool $newestFirst = false;

    /** Jumlah kolom isian di form tambah/ubah. */
    protected static int $formColumns = 1;

    /**
     * Kolom data menu ini; masing-masing jadi isian form dan kolom tabel.
     *
     * @return array<Field>
     */
    protected static function fields(): array
    {
        return [];
    }

    /**
     * Tombol per baris; ganti jika data tidak boleh dihapus.
     *
     * @return array<Action>
     */
    protected static function rowActions(): array
    {
        return [EditAction::make(), DeleteAction::make()];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(static::$formColumns)
            ->components(array_map(static fn(Field $field) => $field->toFormComponent(), static::fields()));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(array_map(static fn(Field $field) => $field->toColumn(), static::fields()))
            ->recordActions(static::rowActions())
            ->defaultSort(static::$sortBy, static::$newestFirst ? 'desc' : 'asc');
    }

    public static function getPages(): array
    {
        return ['index' => static::$managePage::route('/')];
    }

    /** Pengaman jika policy model lupa didaftarkan di AppServiceProvider. */
    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        return $user instanceof User && $user->isAdmin();
    }
}
