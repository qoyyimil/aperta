<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /** Simpan nomor dalam satu format (08xx) agar klien yang sama dikenali. */
    public static function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D/', '', $raw);

        return str_starts_with($digits, '62') ? '0' . substr($digits, 2) : $digits;
    }

    /** Dipakai untuk verifikasi halaman lacak (TRK-1). */
    public function whatsappMatches(string $lastFour): bool
    {
        return str_ends_with(preg_replace('/\D/', '', $this->whatsapp), $lastFour);
    }
}
