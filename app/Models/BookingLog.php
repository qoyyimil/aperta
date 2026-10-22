<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Log audit perubahan booking (MGT-3, SEC-9). Hanya ditambah, tidak pernah diubah. */
class BookingLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
