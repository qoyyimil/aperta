<?php

namespace App\Http\Controllers;

use App\Actions\PlaceBooking;
use App\Http\Requests\StoreBookingRequest;
use App\Mail\BookingPlaced;
use App\Models\Addon;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Booking/Create', [
            'packages' => Package::active()->orderBy('service')->orderBy('price')
                ->get(['id', 'service', 'name', 'duration_minutes', 'price', 'edit_quota']),
            'addons' => Addon::active()->orderBy('name')->get(['id', 'name', 'price', 'max_qty']),
            'cities' => config('aperta.cities'),
            'depositPercent' => config('aperta.min_deposit_percent'),
            'window' => [
                'min' => today()->addDays(config('aperta.min_lead_days'))->toDateString(),
                'max' => today()->addDays(config('aperta.booking_window_days'))->toDateString(),
            ],
            'fullPaymentDays' => config('aperta.full_payment_days_before'),
        ]);
    }

    public function store(StoreBookingRequest $request, PlaceBooking $place): RedirectResponse
    {
        $booking = $place->handle($request->validated());
        Mail::to($booking->client)->send(new BookingPlaced($booking));

        return to_route('track.show', $booking->access_token)
            ->with('success', "Booking terkirim! Kode booking kamu {$booking->code}, simpan untuk melacak status.");
    }
}
