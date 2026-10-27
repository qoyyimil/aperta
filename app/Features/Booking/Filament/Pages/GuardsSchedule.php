<?php

namespace App\Features\Booking\Filament\Pages;

use App\Features\Booking\Availability;
use App\Features\Booking\SlotGrid;
use Filament\Notifications\Notification;
use Illuminate\Support\Carbon;

/** Tolak simpan jika jam yang dipilih admin melebihi kapasitas studio (MGT-5). */
trait GuardsSchedule
{
    protected function guardSchedule(?int $ignore = null): void
    {
        ['session_date' => $date, 'starts_at' => $start, 'ends_at' => $end] = $this->data;
        $duration = SlotGrid::min($end) - SlotGrid::min($start);

        if (!Availability::make()->fits(Carbon::parse($date), $start, $duration, $ignore)) {
            Notification::make()
                ->danger()
                ->title('Jadwal bentrok')
                ->body('Kapasitas fotografer pada jam tersebut sudah penuh. Pilih jam lain.')
                ->send();
            $this->halt();
        }
    }
}
