<?php

namespace App\Features\Booking\Filament;

use App\Features\Booking\Filament\Schemas\BookingForm;
use App\Features\Booking\Filament\Schemas\BookingInfolist;
use App\Features\Booking\Filament\Tables\BookingsTable;
use App\Features\Delivery\Filament\ResultsRelationManager;
use App\Features\Payments\Filament\PaymentsRelationManager;
use App\Features\Proofs\Filament\ProofsRelationManager;
use App\Models\Booking;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;
    protected static ?string $modelLabel = 'booking';
    protected static ?string $recordTitleAttribute = 'code';
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return BookingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BookingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
    }

    /** Fotografer & editor hanya melihat booking yang ditugaskan (BR-10). */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('client')->visibleTo(auth()->user());
    }

    public static function getRelations(): array
    {
        return [
            ProofsRelationManager::class,
            ResultsRelationManager::class,
            PaymentsRelationManager::class,
            LogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBookings::route('/'),
            'create' => Pages\CreateBooking::route('/create'),
            'view' => Pages\ViewBooking::route('/{record}'),
            'edit' => Pages\EditBooking::route('/{record}/edit'),
        ];
    }
}
