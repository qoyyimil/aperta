<?php

namespace App\Enums;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BookingStatus: string implements HasLabel, HasColor
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Shot = 'shot';
    case Selecting = 'selecting';
    case Editing = 'editing';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function getLabel(): string
    {
        return match ($this) {
            self::New => 'Baru',
            self::Confirmed => 'Terkonfirmasi',
            self::Shot => 'Sesi Selesai',
            self::Selecting => 'Pemilihan Foto',
            self::Editing => 'Editing',
            self::Delivered => 'Hasil Terkirim',
            self::Cancelled => 'Dibatalkan',
        };
    }

     public function getColor(): string
    {
        return match ($this) {
            self::New => 'gray',
            self::Confirmed, self::Shot => 'info',
            self::Selecting => 'warning',
            self::Editing => 'primary',
            self::Delivered => 'success',
            self::Cancelled => 'danger',
        };
    }

    /** @return self[] Status tujuan yang sah (SRS Lampiran B). */
    public function transitions(): array
    {
        return match ($this) {
            self::New => [self::Confirmed, self::Cancelled],
            self::Confirmed => [self::Shot, self::Cancelled],
            self::Shot => [self::Selecting],
            self::Selecting => [self::Editing],
            self::Editing => [self::Selecting, self::Delivered],
            default => [],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->transitions(), true);
    }

    /** Klien hanya boleh mengubah pilihan foto di status ini (SEL-9). */
    public function selectionOpen(): bool
    {
        return $this === self::Selecting;
    }

    /** Galeri proof bisa dibuka klien sejak dipublikasikan (dibaca saja setelah dikirim). */
    public function hasGallery(): bool
    {
        return in_array($this, [self::Selecting, self::Editing, self::Delivered], true);
    }

    public function hasResults(): bool
    {
        return in_array($this, [self::Editing, self::Delivered], true);
    }
}
