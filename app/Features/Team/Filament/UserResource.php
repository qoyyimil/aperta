<?php

namespace App\Features\Team\Filament;

use App\Enums\UserRole;
use App\Filament\SimpleResource;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

/** Pengguna tidak dihapus, hanya dinonaktifkan, agar riwayatnya tetap utuh (USR-3). */
class UserResource extends SimpleResource
{
    protected static ?string $model = User::class;
    protected static ?string $modelLabel = 'pengguna';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';
    protected static string $managePage = ManageUsers::class;
    protected static int $formColumns = 2;

    // Kata sandi hanya wajib saat menambah, jadi isiannya ditulis langsung.
    public static function form(Schema $schema): Schema
    {
        return parent::form($schema)
            ->components([
                TextInput::make('name')->label('Nama')->required(),
                TextInput::make('email')->email()->required()->unique(ignoreRecord: true),
                Select::make('role')->label('Peran')->options(UserRole::class)->required(),
                TextInput::make('password')
                    ->label('Kata sandi')
                    ->password()
                    ->revealable()
                    ->minLength(10)
                    ->required(fn(string $operation) => $operation === 'create')
                    ->dehydrated(fn(?string $state) => filled($state))
                    ->helperText('Kosongkan jika tidak ingin mengganti.'),
                Toggle::make('is_active')->label('Aktif')->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return parent::table($table)
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('role')->label('Peran')->badge(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
            ]);
    }

    protected static function rowActions(): array
    {
        return [EditAction::make()];
    }
}
