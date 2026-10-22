<?php

namespace App\Models;

use App\Enums\DiscountType;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $guarded = ['id', 'used'];

    protected function casts(): array
    {
        return ['type' => DiscountType::class, 'starts_on' => 'date', 'ends_on' => 'date'];
    }

    /** Alasan voucher tidak berlaku, atau null jika berlaku (BOOK-7). */
    public function rejection(): ?string
    {
        return match (true) {
            $this->starts_on?->isFuture() => 'Voucher belum berlaku.',
            $this->ends_on?->endOfDay()->isPast() => 'Voucher sudah kedaluwarsa.',
            $this->quota !== null && $this->used >= $this->quota => 'Kuota voucher sudah habis.',
            default => null,
        };
    }

    public function discountFor(int $amount): int
    {
        return $this->type->apply($this->value, $amount);
    }
}
