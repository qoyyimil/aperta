<?php

namespace App\Models;

use App\Models\Concerns\HasRenderedImages;
use App\Models\Contracts\Renderable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EditedPhoto extends Model implements Renderable
{
    use HasRenderedImages, SoftDeletes;

    protected $guarded = ['id'];

    public function booking(): BelongsTo { return $this->belongsTo(Booking::class); }
    public function proof(): BelongsTo { return $this->belongsTo(ProofPhoto::class, 'proof_photo_id'); }
    public function uploader(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }

    /**
     * Cari foto pilihan yang namanya termuat di nama file hasil (DLV-2).
     * Nama terpanjang menang, agar DSC_4512_edit tidak tertukar dengan DSC_45.
     */
    public function matchProof(?Collection $selected = null): static
    {
        $name = strtolower($this->filename);
        $proof = ($selected ?? $this->booking->selected()->get())
            ->filter(fn (ProofPhoto $p) => str_contains($name, strtolower($p->basename())))
            ->sortByDesc(fn (ProofPhoto $p) => strlen($p->basename()))
            ->first();

        $this->proof()->associate($proof);

        return $this;
    }

    // --- Renderable: pratinjau berwatermark, file asli tanpa watermark disimpan untuk diunduh (DLV-8) ---

    public function sourcePath(): string { return $this->path; }
    public function renderBase(): string { return "results/{$this->booking_id}/renders/{$this->id}"; }

    public function rendered(string $preview, string $thumb): void
    {
        $this->update(['preview_path' => $preview, 'thumb_path' => $thumb, 'error' => null]);
    }

    protected function fileColumns(): array
    {
        return ['path', 'thumb_path', 'preview_path'];
    }
}
