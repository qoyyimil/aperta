<?php

namespace App\Support;

use Intervention\Image\Alignment;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageInterface;
use Intervention\Image\Interfaces\ImageManagerInterface;

/**
 * Membuat versi foto yang boleh dilihat klien (PRF-3): preview 2048px berwatermark dan thumbnail 480px.
 * Hasil di-encode ulang tanpa EXIF, sehingga lokasi GPS ikut terbuang (SEC-6).
 */
final class ProofImages
{
    public const PREVIEW = 2048;
    public const THUMB = 480;

    public function __construct(
        private ImageManagerInterface $images,
    ) {}

    public static function make(): self
    {
        return new self(ImageManager::usingDriver(Driver::class));
    }

    /** @return array{preview: string, thumb: string} isi file JPEG */
    public function render(string $binary, ?string $watermarkPath = null): array
    {
        $image = $this->images->decodeBinary($binary)->orient()->scaleDown(self::PREVIEW, self::PREVIEW);

        if ($watermarkPath && is_file($watermarkPath)) {
            // trim() membuang margin transparan logo, agar jarak antar-logo sesuai bagian yang terlihat.
            $mark = $this->images->decodePath($watermarkPath)->scaleDown(width: max(1, intdiv($image->width(), 4)));
            $this->tile($image, $mark->trim());
        }

        return [
            'preview' => $this->jpeg($image),
            'thumb' => $this->jpeg($image->scaleDown(self::THUMB, self::THUMB)),
        ];
    }

    /** Watermark berulang selang-seling, agar tidak bisa dibuang dengan crop. */
    private function tile(ImageInterface $image, ImageInterface $mark): void
    {
        $stepX = max(1, intdiv($mark->width() * 3, 2));
        $stepY = max(1, $mark->height());

        for ($y = 0, $row = 0; $y < $image->height(); $y += $stepY, $row++) {
            for ($x = ($row % 2) * intdiv($stepX, 2); $x < $image->width(); $x += $stepX) {
                $image->insert($mark, $x, $y, Alignment::TOP_LEFT, transparency: 0.4);
            }
        }
    }

    private function jpeg($image): string
    {
        return (string) $image->encodeUsingFormat(Format::JPEG, quality: 82, strip: true);
    }
}
