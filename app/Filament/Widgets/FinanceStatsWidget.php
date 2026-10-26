<?php

namespace App\Filament\Widgets;

use App\Models\Booking;
use App\Models\Payment;
use App\Support\Rupiah;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    // Angka cukup diperbarui saat halaman dimuat ulang.
    protected ?string $pollingInterval = null;

    public static function canView(): bool
    {
        return auth()->user()->isAdmin();
    }

    protected function getStats(): array
    {
        return [
            Stat::make('Uang masuk bulan ini', Rupiah::format((int) Payment::thisMonth()->sum('amount')))
                ->icon(Heroicon::OutlinedBanknotes),
            Stat::make('Piutang', Rupiah::format(Booking::receivables()))
                ->description('Sisa tagihan booking aktif')
                ->icon(Heroicon::OutlinedClock),
            Stat::make('Booking bulan ini', Booking::thisMonth()->count())
                ->description('Tanpa yang dibatalkan')
                ->icon(Heroicon::OutlinedCalendarDays),
        ];
    }
}
