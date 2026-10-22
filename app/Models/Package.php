<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** Data yang disalin ke booking saat dibuat (BR-2). */
    public function snapshot(): array
    {
        return [
            'package_id' => $this->id,
            'service' => $this->service,
            'package_name' => $this->name,
            'package_price' => $this->price,
            'edit_quota' => $this->edit_quota,
            'extra_photo_price' => $this->extra_photo_price,
        ];
    }

    public function scopeActive(Builder $q): void
    {
        $q->where('is_active', true);
    }
}
