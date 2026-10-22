<?php

namespace App\Support;

use Intervention\Image\Alignment;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Format;
use Intervention\Image\ImageManager;
use Intervention\Image\Interfaces\ImageManagerInterface;

/**
 * Membuat versi foto yang boleh dilihat klien (PRF-3): preview 2048px berwatermark dan thumbnail 480px.
 * Hasil di-encode ulang tanpa EXIF, sehingga lokasi GPS ikut terbuang (SEC-6).
 */
final class ProofImages
{
    public const PREVIEW = 2048;
    public const THUMB = 480;

    public function __construct(private ImageManagerInterface $images) {}

    public static function make(): self
    {
        return new self(ImageManager::usingDriver(Driver::class));
    }

    /** @return array{preview: string, thumb: string} isi file JPEG */
    public function render(string $binary, ?string $watermarkPath = null): array
    {
        $image = $this->images->decodeBinary($binary)->orient()->scaleDown(self::PREVIEW, self::PREVIEW);

        if ($watermarkPath && is_file($watermarkPath)) {
            $mark = $this->images->decodePath($watermarkPath)->scaleDown(width: intdiv($image->width(), 4));
            $image->insert($mark, 24, 24, Alignment::BOTTOM_RIGHT, 0.6);
        }

        return [
            'preview' => $this->jpeg($image),
            'thumb' => $this->jpeg($image->scaleDown(self::THUMB, self::THUMB)),
        ];
    }

    private function jpeg($image): string
    {
        return (string) $image->encodeUsingFormat(Format::JPEG, quality: 82, strip: true);
    }
}
