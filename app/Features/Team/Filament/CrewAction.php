<?php

namespace App\Features\Team\Filament;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/** Menugaskan fotografer/editor ke booking (MGT-4). */
final class CrewAction
{
    public static function make(): Action
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
}
