<?php

namespace App\Models;

use App\Support\Rupiah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['paid_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::creating(fn(self $p) => $p->recorded_by ??= auth()->id());
        static::created(fn(self $p) => $p->booking->confirmIfDeposited());
        static::deleted(fn(self $p) => $p->booking->log(
            'payment',
            reason: 'Pembayaran ' . Rupiah::format($p->amount) . ' dihapus',
        ));
    }

    public function scopeThisMonth(Builder $query): void
    {
        $query->whereBetween('paid_on', [today()->startOfMonth(), today()->endOfMonth()]);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
