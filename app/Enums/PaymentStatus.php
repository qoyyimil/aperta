<?php

namespace App\Enums;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PaymentStatus: string implements HasLabel, HasColor
{
    case Unpaid = 'unpaid';
    case Deposit = 'deposit';
    case Paid = 'paid';

    public static function fromAmounts(int $paid, int $total, int $minDeposit): self
    {
        return match (true) {
            $paid >= $total => self::Paid,
            $paid > 0 && $paid >= $minDeposit => self::Deposit,
            default => self::Unpaid,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Bayar',
            self::Deposit => 'DP',
            self::Paid => 'Lunas',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Unpaid => 'danger',
            self::Deposit => 'warning',
            self::Paid => 'success',
        };
    }
}
