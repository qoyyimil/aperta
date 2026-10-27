<?php

namespace App\Features\Payments;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use Illuminate\Support\Carbon;

/**
 * Bagian Booking untuk pembayaran: DP, pelunasan, dan piutang (PAY).
 *
 * @mixin Booking
 */
trait BookingPayments
{
    public function paid(): int
    {
        return (int) (
            $this->hasAttribute('payments_sum_amount') ? $this->payments_sum_amount : $this->payments()->sum('amount')
        );
    }

    /** Sisa seluruh tagihan, termasuk foto tambahan. */
    public function balance(): int
    {
        return max(0, $this->price()->total() - $this->paid());
    }

    /** Total piutang booking aktif. shortcut: dijumlah di PHP, pindah ke SQL jika booking aktif ribuan. */
    public static function receivables(): int
    {
        return static::active()->withBilling()->get()->sum->balance();
    }

    public function minDeposit(): int
    {
        return intdiv($this->price()->total() * config('aperta.min_deposit_percent'), 100);
    }

    public function paymentStatus(): PaymentStatus
    {
        return PaymentStatus::fromAmounts($this->paid(), $this->price()->total(), $this->minDeposit());
    }

    /** Batas pelunasan tagihan sesi, mis. H-1 sebelum sesi. */
    public function paymentDueOn(): Carbon
    {
        return $this->session_date->copy()->subDays(config('aperta.full_payment_days_before'));
    }

    /** Batas bayar DP sejak booking dibuat, tapi tidak melewati jatuh tempo pelunasan. */
    public function depositDueAt(): Carbon
    {
        return $this->created_at
            ->copy()
            ->addHours(config('aperta.deposit_hours'))
            ->min($this->paymentDueOn()->endOfDay());
    }

    public function sessionBalance(): int
    {
        return max(0, $this->price()->sessionTotal() - $this->paid());
    }

    public function isSessionPaid(): bool
    {
        return $this->sessionBalance() === 0;
    }

    /** Sesi belum berlangsung, jatuh tempo lewat, dan tagihan sesi belum lunas. */
    public function isPaymentOverdue(): bool
    {
        return (
            in_array($this->status, [BookingStatus::New, BookingStatus::Confirmed], true)
            && today()->gt($this->paymentDueOn())
            && !$this->isSessionPaid()
        );
    }

    /** Booking baru otomatis terkonfirmasi saat DP cukup (PAY-3). */
    public function confirmIfDeposited(): void
    {
        if ($this->status === BookingStatus::New && $this->paymentStatus() !== PaymentStatus::Unpaid) {
            $this->transitionTo(BookingStatus::Confirmed, 'DP diterima');
        }
    }
}
