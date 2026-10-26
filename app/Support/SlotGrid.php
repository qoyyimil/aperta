<?php

namespace App\Support;

/**
 * Grid slot harian dengan kapasitas tetap per slot. Murni hitungan, tanpa database.
 * Waktu ditulis "HH:MM"; $booked berisi pasangan [mulai, selesai] booking yang sudah ada.
 */
final readonly class SlotGrid
{
    public function __construct(
        public string $open,
        public string $close,
        public int $step,
        public int $capacity,
    ) {}

    public static function fromConfig(int $capacity): self
    {
        ['open' => $open, 'close' => $close, 'minutes' => $step] = config('aperta.slots');

        return new self($open, $close, $step, max(1, $capacity));
    }

    /** Jam mulai yang masih muat untuk sesi berdurasi $duration menit. */
    public function startTimes(int $duration, array $booked = []): array
    {
        $times = [];
        for ($t = self::min($this->open); ($t + $duration) <= self::min($this->close); $t += $this->step) {
            if ($this->fitsAt($t, $duration, $booked)) {
                $times[] = self::time($t);
            }
        }

        return $times;
    }

    /** Untuk klien: harus tepat di awal slot dan di dalam jam operasional. */
    public function allows(string $start, int $duration, array $booked = []): bool
    {
        return in_array(substr($start, 0, 5), $this->startTimes($duration, $booked), true);
    }

    /** Untuk admin: jam bebas, hanya cek kapasitas (MGT-5). */
    public function fits(string $start, int $duration, array $booked = []): bool
    {
        return $this->fitsAt(self::min($start), $duration, $booked);
    }

    private function fitsAt(int $start, int $duration, array $booked): bool
    {
        for ($t = $start; $t < ($start + $duration); $t += $this->step) {
            $end = min($t + $this->step, $start + $duration);
            $used = count(array_filter($booked, fn(array $b) => self::min($b[0]) < $end && self::min($b[1]) > $t));
            if ($used >= $this->capacity) {
                return false;
            }
        }

        return true;
    }

    public static function min(string $time): int
    {
        [$h, $m] = explode(':', $time);

        return ((int) $h * 60) + (int) $m;
    }

    public static function time(int $minutes): string
    {
        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }
}
