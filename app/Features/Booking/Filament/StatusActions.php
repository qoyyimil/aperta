<?php

namespace App\Features\Booking\Filament;

use App\Enums\BookingStatus;
use App\Models\Booking;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * Tombol ubah status yang dijalankan manual dari dashboard.
 * Status lain berubah lewat alurnya sendiri (unggah proof, klien kirim pilihan, kirim hasil).
 */
final class StatusActions
{
    /** @return Action[] */
    public static function all(): array
    {
        return [
            self::make(BookingStatus::Confirmed, 'Konfirmasi manual', Heroicon::OutlinedCheckBadge),
            self::make(BookingStatus::Shot, 'Tandai sesi selesai', Heroicon::OutlinedCamera),
            self::make(BookingStatus::Cancelled, 'Batalkan booking', Heroicon::OutlinedXCircle, withReason: true),
        ];
    }

    private static function make(BookingStatus $to, string $label, Heroicon $icon, bool $withReason = false): Action
    {
        return Action::make("to_{$to->value}")
            ->label($label)
            ->icon($icon)
            ->color($to->getColor())
            ->requiresConfirmation()
            ->visible(
                fn(Booking $record) => (
                    $record->status->canTransitionTo($to) && auth()->user()->can('transition', [$record, $to])
                ),
            )
            ->disabled(fn(Booking $record) => filled($record->transitionBlocker($to)))
            ->tooltip(fn(Booking $record) => $record->transitionBlocker($to))
            ->schema($withReason ? [Textarea::make('reason')->label('Alasan')->required()->maxLength(255)] : [])
            ->action(function (Booking $record, array $data) use ($to) {
                $record->transitionTo($to, $data['reason'] ?? null);
                Notification::make()->title("Status diubah ke {$to->getLabel()}")->success()->send();
            });
    }
}
