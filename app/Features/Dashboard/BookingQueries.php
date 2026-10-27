<?php

namespace App\Features\Dashboard;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Bagian Booking untuk kueri dan antrean dashboard.
 *
 * @mixin Booking
 */
trait BookingQueries
{
    public function scopeThisMonth(Builder $query): void
    {
        $query->active()->whereBetween('session_date', [today()->startOfMonth(), today()->endOfMonth()]);
    }

    public function scopeUpcoming(Builder $query, int $days = 7): void
    {
        $query
            ->active()
            ->whereBetween('session_date', [today(), today()->addDays($days)])
            ->orderBy('session_date')
            ->orderBy('starts_at');
    }

    /** Menunggu admin: belum DP, lunas mendekati jatuh tempo, galeri belum dibuka, batas pilih dekat. */
    public function scopeNeedsAction(Builder $query): void
    {
        $soon = today()->addDays(2);
        // shortcut: lunas dicek di PHP; kandidatnya hanya Confirmed beberapa hari ke depan.
        $unpaid = static::where('status', BookingStatus::Confirmed)
            ->whereDate('session_date', '<=', $soon->copy()->addDays(config('aperta.full_payment_days_before')))
            ->withBilling()
            ->get()
            ->reject
            ->isSessionPaid()
            ->modelKeys();

        $query->where(fn(Builder $query) => $query
            ->whereIn('status', [BookingStatus::New, BookingStatus::Shot])
            ->orWhereKey($unpaid)
            ->orWhere(
                fn(Builder $query) => $query->where('status', BookingStatus::Selecting)->whereDate(
                    'selection_deadline',
                    '<=',
                    $soon,
                ),
            ))->orderBy('session_date');
    }

    public function scopeAwaitingProofs(Builder $query): void
    {
        $query->where('status', BookingStatus::Shot)->doesntHave('proofs')->orderBy('session_date');
    }

    public function scopeEditingQueue(Builder $query): void
    {
        $query->where('status', BookingStatus::Editing)->orderBy('selection_submitted_at');
    }

    /** Antrean dashboard sesuai peran, lengkap dengan hitungan untuk actionReason(). */
    public function scopeQueueFor(Builder $query, User $user): void
    {
        match ($user->role) {
            UserRole::Admin => $query->needsAction(),
            UserRole::Photographer => $query->awaitingProofs(),
            UserRole::Editor => $query->editingQueue(),
        };

        $query->visibleTo($user)->with('client')->withBilling();
    }

    /** Muat hitungan untuk paid(), extraPhotos(), dan actionReason() tanpa kueri per baris. */
    public function scopeWithBilling(Builder $query): void
    {
        $selected = fn($query) => $query->where('is_selected', true);

        $query
            ->with('addons')
            ->withSum('payments', 'amount')
            ->withCount([
                'proofs',
                'proofs as selected_count' => $selected,
                'proofs as notes_count' => fn($query) => $selected($query)->whereNotNull('note'),
                'proofs as missing_results_count' => fn($query) => $selected($query)->doesntHave('results'),
            ]);
    }

    /** Alasan singkat di antrean dashboard. Butuh withBilling(). */
    public function actionReason(): ?string
    {
        return match ($this->status) {
            BookingStatus::New => ($this->depositDueAt()->isPast() ? 'DP lewat batas ' : 'Belum DP, batas ')
                . $this->depositDueAt()->translatedFormat('j M H:i'),
            BookingStatus::Confirmed => ($this->isPaymentOverdue() ? 'Lewat jatuh tempo ' : 'Lunas jatuh tempo ')
                . $this->paymentDueOn()->translatedFormat('j M'),
            BookingStatus::Shot => $this->proofs_count ? 'Galeri belum dibuka' : 'Proof belum diunggah',
            BookingStatus::Selecting => $this->selectionReason(),
            BookingStatus::Editing => "{$this->selected_count} foto, {$this->notes_count} catatan, "
                . "{$this->missing_results_count} belum ada hasil",
            default => null,
        };
    }

    private function selectionReason(): string
    {
        $days = (int) today()->diffInDays($this->selection_deadline, false);

        return match (true) {
            $days > 0 => "Batas pilih {$days} hari lagi",
            $days === 0 => 'Batas pilih hari ini',
            default => 'Batas pilih lewat ' . -$days . ' hari',
        };
    }
}
