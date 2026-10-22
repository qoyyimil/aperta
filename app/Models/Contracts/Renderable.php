<?php

namespace App\Models\Contracts;

/** Foto yang dibuatkan thumbnail & preview oleh job RenderPhoto. */
interface Renderable
{
    /** Path file sumber yang akan diolah. */
    public function sourcePath(): string;

    /** Path watermark, atau null jika tanpa watermark. */
    public function watermark(): ?string;

    /** Awalan path untuk file hasil olahan, tanpa akhiran. */
    public function renderBase(): string;

    /** Dipanggil setelah thumbnail & preview tersimpan. */
    public function rendered(string $preview, string $thumb): void;
}
