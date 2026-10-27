<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Features\Dashboard\BookingQueries;
use App\Features\Delivery\BookingDelivery;
use App\Features\Payments\BookingPayments;
use App\Features\Proofs\BookingProofs;
use App\Features\Selection\BookingSelection;
use App\Support\BookingCode;
use App\Support\PriceBreakdown;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/** Inti booking: relasi, status, harga. Aturan per fitur ada di trait app/Features/*. */
class Booking extends Model
{
    use BookingDelivery;
    use BookingPayments;
    use BookingProofs;
    use BookingQueries;
    use BookingSelection;
    use HasFactory;
    use SoftDeletes;

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
            $b->code ??= BookingCode::generate(fn($c) => static::withTrashed()->where('code', $c)->exists());
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
        $get = fn(string $key) => $original ? $this->getRawOriginal($key) : $this->getAttributes()[$key];

        return Carbon::parse($get('session_date'))->format('Y-m-d') . ' ' . substr($get('starts_at'), 0, 5);
    }

    // --- relasi ---

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function proofs(): HasMany
    {
        return $this->hasMany(ProofPhoto::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(EditedPhoto::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(BookingLog::class);
    }

    public function crew(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Addon::class)->withPivot('qty', 'price');
    }

    // --- status ---

    public function transitionTo(BookingStatus $to, ?string $reason = null): static
    {
        if (!$this->status->canTransitionTo($to)) {
            throw new InvalidStatusTransition($this->status, $to);
        }

        if ($blocker = $this->transitionBlocker($to)) {
            throw new DomainException($blocker);
        }

        $from = $this->status;
        $this
            ->forceFill(['status' => $to])
            ->fill($to === BookingStatus::Cancelled ? ['cancel_reason' => $reason] : [])
            ->save();
        $this->log('status', $from->value, $to->value, $reason);

        return $this;
    }

    /** Alasan aturan bisnis menolak transisi, atau null jika boleh. */
    public function transitionBlocker(BookingStatus $to): ?string
    {
        return match (true) {
            $to === BookingStatus::Shot && !$this->isSessionPaid() => 'Tagihan sesi belum lunas (jatuh tempo '
                . $this->paymentDueOn()->translatedFormat('d M Y')
                . '). Catat pelunasan dulu di tab Pembayaran.',
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

    // --- tim ---

    /** Atur fotografer/editor yang ditugaskan (MGT-4) dan catat di riwayat. */
    public function assignCrew(array $userIds): void
    {
        $this->crew()->sync($userIds);
        $this->log('crew', reason: User::whereKey($userIds)->pluck('name')->join(', ') ?: 'Tidak ada');
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
            addons: $this->addons->sum(fn($a) => $a->pivot->qty * $a->pivot->price),
            extras: $this->extraPhotos() * $this->extra_photo_price,
            discount: $this->discount,
        );
    }

    // --- akses ---

    /** Satu sumber aturan akses: admin semua, tim hanya yang ditugaskan (BR-10). */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->unless($user->isAdmin(), fn(Builder $query) => $query->whereRelation('crew', 'users.id', $user->id));
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('status', '!=', BookingStatus::Cancelled);
    }
}
