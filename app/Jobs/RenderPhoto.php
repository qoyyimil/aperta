<?php

namespace App\Jobs;

use App\Models\Contracts\Renderable;
use App\Support\ProofImages;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Throwable;

/** Membuat thumbnail & preview untuk proof maupun hasil edit (PRF-3, DLV-7). */
class RenderPhoto implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public Model&Renderable $photo) {}

    public function handle(): void
    {
        $disk = Storage::disk(config('aperta.photo_disk'));
        $base = $this->photo->renderBase();
        $images = ProofImages::make()->render($disk->get($this->photo->sourcePath()), config('aperta.watermark'));

        $disk->put("{$base}-preview.jpg", $images['preview']);
        $disk->put("{$base}-thumb.jpg", $images['thumb']);
        $this->photo->rendered("{$base}-preview.jpg", "{$base}-thumb.jpg");
    }

    public function failed(Throwable $e): void
    {
        $this->photo->update(['error' => 'File tidak bisa diproses. Pastikan formatnya JPG/PNG yang valid.']);
    }
}
