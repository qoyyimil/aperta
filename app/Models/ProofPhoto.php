<?php

namespace App\Models;

use App\Models\Concerns\HasRenderedImages;
use App\Models\Contracts\Renderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProofPhoto extends Model implements Renderable
{
    use HasRenderedImages, SoftDeletes;

    protected $guarded = ['id'];

    protected $attributes = ['is_selected' => false];

    protected function casts(): array
    {
        return ['is_selected' => 'boolean'];
    }

    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function results(): HasMany { return $this->hasMany(EditedPhoto::class); }

    // --- Renderable: proof diberi watermark, file asli dihapus setelah diolah (PRF-3) ---

    public function sourcePath(): string { return $this->source_path; }
    public function watermark(): ?string { return config('aperta.watermark'); }
    public function renderBase(): string { return "proofs/{$this->booking_id}/{$this->frame}_{$this->basename()}"; }

    public function rendered(string $preview, string $thumb): void
    {
        $source = $this->source_path;
        $this->update(['preview_path' => $preview, 'thumb_path' => $thumb, 'source_path' => null, 'error' => null]);
        self::disk()->delete($source);
    }

    protected function fileColumns(): array
    {
        return ['source_path', 'thumb_path', 'preview_path'];
    }

    /** Nama file tanpa ekstensi, kunci pencocokan hasil edit (DLV-2). */
    public function basename(): string
    {
        return pathinfo($this->filename, PATHINFO_FILENAME);
    }

    /** Menulis catatan otomatis memilih foto (SEL-6). */
    public function annotate(?string $note): static
    {
        $note = trim((string) $note) ?: null;

        return tap($this)->update(['note' => $note, 'is_selected' => $this->is_selected || $note]);
    }
}
