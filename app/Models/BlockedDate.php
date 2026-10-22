<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Tanggal yang tidak bisa dipesan klien, mis. libur atau acara studio. */
class BlockedDate extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['date' => 'date'];
    }
}
