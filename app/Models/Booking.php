<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Exceptions\InvalidStatusTransition;
use App\Support\BookingCode;
use App\Support\PriceBreakdown;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use DomainException;

class Booking extends Model
{
    use HasFactory, SoftDeletes;

    /** Kolom ini hanya berubah lewat method di model, bukan dari input form. */
    protected $guarded = ['id', 'code', 'access_token', 'status'];
    protected $attributes = ['discount' => 0];

    protected function casts(): array
    {
        return [
            'status' => BookingStatus::class,
            'session_date' => 'date',
            'selection_deadline' => 'date',
            'selection_submitted_at' => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $b) {
            $b->code ??= BookingCode::generate(fn ($c) => static::withTrashed()->where('code', $c)->exists());
            $b->access_token ??= bin2hex(random_bytes(24));
            $b->status ??= BookingStatus::New;
        });

        static::updated(function (self $b) {
            if ($b->wasChanged(['session_date', 'starts_at'])) {
                $b->log('reschedule', reason: $b->schedule(original: true) . ' → ' . $b->schedule());
            }
        });
    }

    /** Jadwal sebagai teks "2026-11-08 09:00", untuk log (MGT-3). */
    public function schedule(bool $original = false): string
    {
        $get = fn (string $key) => $original ? $this->getRawOriginal($key) : $this->getAttributes()[$key];

        return Carbon::parse($get('session_date'))->format('Y-m-d') . ' ' . substr($get('starts_at'), 0, 5);
    }

    // --- relasi ---

    public function client(): BelongsTo { return $this->belongsTo(Client::class); }
    public function package(): BelongsTo { return $this->belongsTo(Package::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function proofs(): HasMany { return $this->hasMany(ProofPhoto::class); }
    public function results(): HasMany { return $this->hasMany(EditedPhoto::class); }
    public function logs(): HasMany { return $this->hasMany(BookingLog::class); }
    public function crew(): BelongsToMany { return $this->belongsToMany(User::class); }

    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class)->withPivot('qty', 'price');
    }

    public function selected(): HasMany
    {
        return $this->proofs()->where('is_selected', true);
    }

    // --- status ---

    public function transitionTo(BookingStatus $to, ?string $reason = null): static
    {
        if (! $this->status->canTransitionTo($to)) {
            throw new InvalidStatusTransition($this->status, $to);
        }

        if ($blocker = $this->transitionBlocker($to)) {
            throw new DomainException($blocker);
        }

        $from = $this->status;
        $this->forceFill(['status' => $to])
            ->fill($to === BookingStatus::Cancelled ? ['cancel_reason' => $reason] : [])
            ->save();
        $this->log('status', $from->value, $to->value, $reason);

        return $this;
    }

    /** Alasan aturan bisnis menolak transisi, atau null jika boleh. */
    public function transitionBlocker(BookingStatus $to): ?string
    {
        return match (true) {
            $to === BookingStatus::Shot && ! $this->isSessionPaid() => 'Tagihan sesi belum lunas (jatuh tempo '
                . $this->paymentDueOn()->translatedFormat('d M Y') . '). Catat pelunasan dulu di tab Pembayaran.',
            default => null,
        };
    }

    public function log(string $action, ?string $from = null, ?string $to = null, ?string $reason = null): void
    {
        $this->logs()->create([
            'user_id' => auth()->id(),
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'reason' => $reason,
        ]);
    }

    // --- tim & proof ---

    /** Atur fotografer/editor yang ditugaskan (MGT-4) dan catat di riwayat. */
    public function assignCrew(array $userIds): void
    {
        $this->crew()->sync($userIds);
        $this->log('crew', reason: User::whereKey($userIds)->pluck('name')->join(', ') ?: 'Tidak ada');
    }

    public function hasPendingProofs(): bool
    {
        return $this->proofs()->whereNull('preview_path')->exists();
    }

    /** Buka galeri untuk klien dengan kuota & batas waktu final (PRF-6, PRF-7). */
    public function publishProofs(int $quota, int $extraPrice, string $deadline): void
    {
        $this->forceFill(['edit_quota' => $quota, 'extra_photo_price' => $extraPrice, 'selection_deadline' => $deadline]);
        $this->transitionTo(BookingStatus::Selecting, "Kuota {$quota} foto, batas pilih {$deadline}");
    }

    /** Klien mengunci pilihannya dan editing dimulai (SEL-8, SEL-9). */
    public function submitSelection(): void
    {
        throw_if($this->selected()->doesntExist(), new DomainException('Pilih minimal satu foto sebelum mengirim.'));

        $this->forceFill(['selection_submitted_at' => now()]);
        $this->transitionTo(BookingStatus::Editing, $this->selected()->count() . ' foto dipilih klien');
    }

    // --- hasil edit ---

    /** Foto pilihan yang belum punya hasil edit (DLV-3). */
    public function missingResults(): HasMany
    {
        return $this->selected()->whereDoesntHave('results');
    }

    public function hasPendingResults(): bool
    {
        return $this->results()->whereNull('preview_path')->exists();
    }

    /** Kirim hasil ke klien (DLV-6). */
    public function deliverResults(): void
    {
        throw_if($this->results()->doesntExist(), new DomainException('Unggah minimal satu hasil edit sebelum mengirim.'));

        $this->forceFill(['delivered_at' => now()]);
        $this->transitionTo(BookingStatus::Delivered, $this->results()->count() . ' hasil edit dikirim');
    }

    /** Unduhan resolusi penuh hanya setelah lunas (BR-6). */
    public function canDownloadResults(): bool
    {
        return $this->status === BookingStatus::Delivered && $this->paymentStatus() === PaymentStatus::Paid;
    }

    // --- paket & harga ---

    /** Salin harga & kuota paket ke booking (BR-2). */
    public function applyPackage(Package $p): static
    {
        return $this->fill($p->snapshot());
    }

    public function extraPhotos(): int
    {
        $selected = $this->hasAttribute('selected_count') ? $this->selected_count : $this->selected()->count();

        return max(0, $selected - $this->edit_quota);
    }

    public function price(): PriceBreakdown
    {
        return new PriceBreakdown(
            package: $this->package_price,
            addons: $this->addons->sum(fn ($a) => $a->pivot->qty * $a->pivot->price),
            extras: $this->extraPhotos() * $this->extra_photo_price,
            discount: $this->discount,
        );
    }

    // --- pembayaran ---

    public function paid(): int
    {
        return (int) ($this->hasAttribute('payments_sum_amount') ? $this->payments_sum_amount : $this->payments()->sum('amount'));
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
        return $this->created_at->copy()->addHours(config('aperta.deposit_hours'))
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
        return in_array($this->status, [BookingStatus::New, BookingStatus::Confirmed], true)
            && today()->gt($this->paymentDueOn())
            && ! $this->isSessionPaid();
    }
    /** Booking baru otomatis terkonfirmasi saat DP cukup (PAY-3). */
    public function confirmIfDeposited(): void
    {
        if ($this->status === BookingStatus::New && $this->paymentStatus() !== PaymentStatus::Unpaid) {
            $this->transitionTo(BookingStatus::Confirmed, 'DP diterima');
        }
    }

    // --- kueri dashboard ---

    /** Satu sumber aturan akses: admin semua, tim hanya yang ditugaskan (BR-10). */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->unless($user->isAdmin(), fn (Builder $query) => $query->whereRelation('crew', 'users.id', $user->id));
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', '!=', BookingStatus::Cancelled);
    }

    public function scopeThisMonth(Builder $query): void
    {
        $query->active()->whereBetween('session_date', [today()->startOfMonth(), today()->endOfMonth()]);
    }

    public function scopeUpcoming(Builder $query, int $days = 7): void
    {
        $query->active()
            ->whereBetween('session_date', [today(), today()->addDays($days)])
            ->orderBy('session_date')->orderBy('starts_at');
    }

    /** Menunggu admin: belum DP, lunas mendekati jatuh tempo, galeri belum dibuka, batas pilih dekat. */
    public function scopeNeedsAction(Builder $query): void
    {
        $soon = today()->addDays(2);
        // shortcut: lunas dicek di PHP; kandidatnya hanya Confirmed beberapa hari ke depan.
        $unpaid = static::where('status', BookingStatus::Confirmed)
            ->whereDate('session_date', '<=', $soon->copy()->addDays(config('aperta.full_payment_days_before')))
            ->withBilling()->get()->reject->isSessionPaid()->modelKeys();

        $query->where(fn (Builder $query) => $query
            ->whereIn('status', [BookingStatus::New, BookingStatus::Shot])
            ->orWhereKey($unpaid)
            ->orWhere(fn (Builder $query) => $query
                ->where('status', BookingStatus::Selecting)
                ->whereDate('selection_deadline', '<=', $soon)))
            ->orderBy('session_date');
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
        $selected = fn ($query) => $query->where('is_selected', true);

        $query->with('addons')->withSum('payments', 'amount')->withCount([
            'proofs',
            'proofs as selected_count' => $selected,
            'proofs as notes_count' => fn ($query) => $selected($query)->whereNotNull('note'),
            'proofs as missing_results_count' => fn ($query) => $selected($query)->doesntHave('results'),
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
