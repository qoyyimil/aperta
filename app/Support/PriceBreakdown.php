<?php

namespace App\Support;

/** Rincian tagihan booking (BOOK-6, SEL-11). Semua nominal dalam rupiah. */
final readonly class PriceBreakdown
{
    public function __construct(
        public int $package,
        public int $addons = 0,
        public int $extras = 0,
        public int $discount = 0,
    ) {}

    public function subtotal(): int
    {
        return $this->package + $this->addons + $this->extras;
    }

    /** Tagihan yang wajib lunas sebelum sesi; foto tambahan baru diketahui setelah klien memilih. */
    public function sessionTotal(): int
    {
        return max(0, $this->package + $this->addons - $this->discount);
    }

    public function total(): int
    {
        return max(0, $this->subtotal() - $this->discount);
    }
}
