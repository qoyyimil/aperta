<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Storage;

/** Perilaku bersama foto yang punya thumbnail & preview (proof dan hasil edit). */
trait HasRenderedImages
{
    /** Kolom path file milik record ini, dihapus bersama record. */
    abstract protected function fileColumns(): array;

    public function isReady(): bool
    {
        return filled($this->preview_path);
    }

    public function failed(): bool
    {
        return filled($this->error);
    }

    /** Tautan sementara ke versi yang aman ditampilkan (SEC-4). */
    public function url(string $kind = 'thumb', int $minutes = 30): ?string
    {
        $path = $kind === 'preview' ? $this->preview_path : $this->thumb_path;

        return $path ? self::disk()->temporaryUrl($path, now()->addMinutes($minutes)) : null;
    }

    /** Hapus permanen beserta semua filenya. */
    public function discard(): void
    {
        self::disk()->delete(array_filter(array_map(fn(string $column) => $this->{$column}, $this->fileColumns())));
        $this->forceDelete();
    }

    protected static function disk()
    {
        return Storage::disk(config('aperta.photo_disk'));
    }
}
