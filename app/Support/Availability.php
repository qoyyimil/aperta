<?php

namespace App\Support;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\User;
use Carbon\CarbonInterface;

/** Ketersediaan jadwal berdasarkan booking yang ada, tanggal diblokir, dan jendela booking. */
final class Availability
{
    public function __construct(
        private SlotGrid $grid,
    ) {}

    /** Kapasitas per slot = jumlah fotografer aktif. */
    public static function make(): self
    {
        $photographers = User::where('role', UserRole::Photographer)->where('is_active', true)->count();

        return new self(SlotGrid::fromConfig($photographers));
    }

    /** Tanggal bisa dipesan klien: dalam jendela booking dan tidak diblokir (BOOK-4). */
    public function isOpen(CarbonInterface $date): bool
    {
        $date = $date->copy()->startOfDay();

        return (
            $date->betweenIncluded(
                today()->addDays(config('aperta.min_lead_days')),
                today()->addDays(config('aperta.booking_window_days')),
            )
            && !BlockedDate::whereDate('date', $date)->exists()
        );
    }

    public function startTimes(CarbonInterface $date, int $duration): array
    {
        return $this->isOpen($date) ? $this->grid->startTimes($duration, $this->booked($date)) : [];
    }

    public function allows(CarbonInterface $date, string $start, int $duration): bool
    {
        return $this->isOpen($date) && $this->grid->allows($start, $duration, $this->booked($date));
    }

    public function fits(CarbonInterface $date, string $start, int $duration, ?int $ignore = null): bool
    {
        return $this->grid->fits($start, $duration, $this->booked($date, $ignore));
    }

    private function booked(CarbonInterface $date, ?int $ignore = null): array
    {
        return Booking::whereDate('session_date', $date)
            ->where('status', '!=', BookingStatus::Cancelled)
            ->when($ignore, fn($q) => $q->whereKeyNot($ignore))
            ->get(['starts_at', 'ends_at'])
            ->map(fn(Booking $b) => [$b->starts_at, $b->ends_at])
            ->all();
    }
}
