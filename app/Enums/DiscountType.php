<?php

namespace App\Enums;
use Filament\Support\Contracts\HasLabel;

enum DiscountType: string implements HasLabel
{
    case Percent = 'percent';
    case Fixed = 'fixed';

    /** Nominal potongan untuk $amount, tidak pernah melebihi $amount. */
    public function apply(int $value, int $amount): int
    {
        return min($amount, match ($this) {
            self::Percent => intdiv($amount * $value, 100),
            self::Fixed => $value,
        });
    }

    public function getLabel(): string
    {
        return $this === self::Percent ? 'Persen' : 'Nominal';
    }
}
