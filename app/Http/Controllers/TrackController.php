<?php

namespace App\Http\Controllers;

use App\Http\Presenters\TrackView;
use App\Models\Booking;
use App\Support\BookingCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

/** Pelacakan status booking tanpa login (fitur TRK). */
class TrackController extends Controller
{
    private const MAX_FAILURES = 5;
    private const LOCK_SECONDS = 15 * 60;

    public function find(): Response
    {
        return Inertia::render('Track/Find');
    }

    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:20'], 'phone' => ['required', 'digits:4']]);
        $key = 'track:' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_FAILURES)) {
            $minutes = ceil(RateLimiter::availableIn($key) / 60);
            throw ValidationException::withMessages([
                'code' => "Terlalu banyak percobaan. Coba lagi dalam {$minutes} menit.",
            ]);
        }

        $booking = Booking::with('client')->firstWhere('code', BookingCode::normalize($data['code']));

        if (!$booking?->client->whatsappMatches($data['phone'])) {
            RateLimiter::hit($key, self::LOCK_SECONDS);
            throw ValidationException::withMessages(['code' => 'Kode booking atau nomor tidak cocok.']);
        }

        RateLimiter::clear($key);

        return to_route('track.show', $booking->access_token);
    }

    public function show(Booking $booking): Response
    {
        return Inertia::render('Track/Show', ['booking' => TrackView::of($booking)]);
    }
}
