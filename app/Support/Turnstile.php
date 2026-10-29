<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use SensitiveParameter;

/** Verifikasi captcha Cloudflare Turnstile di form publik. */
final class Turnstile
{
    private const VERIFY_URL = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    /** Tanpa secret key hanya dilewati di luar produksi; gangguan jaringan dianggap gagal. */
    public static function passes(#[SensitiveParameter] string $token, ?string $ip): bool
    {
        $secret = (string) config('services.turnstile.secret');
        if ($secret === '') {
            return !app()->isProduction();
        }

        return $token !== ''
        && rescue(
            static fn() => (
                Http::asForm()
                    ->timeout(5)
                    ->post(self::VERIFY_URL, ['secret' => $secret, 'response' => $token, 'remoteip' => $ip])
                    ->json('success') === true
            ),
            rescue: false,
        );
    }
}
