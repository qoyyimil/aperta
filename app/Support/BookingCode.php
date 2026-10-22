<?php

namespace App\Support;

/** Kode booking AP-XXXXXX tanpa karakter yang mudah tertukar (BOOK-9). */
final class BookingCode
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function generate(?callable $taken = null): string
    {
        do {
            $code = 'AP-' . self::random(6);
        } while ($taken && $taken($code));

        return $code;
    }

    public static function normalize(string $input): string
    {
        return strtoupper(trim($input));
    }

    private static function random(int $length): string
    {
        $max = strlen(self::ALPHABET) - 1;

        return implode('', array_map(
            fn () => self::ALPHABET[random_int(0, $max)],
            range(1, $length),
        ));
    }
}
