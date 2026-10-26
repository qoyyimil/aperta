<?php

namespace App\Mail;

use App\Http\Presenters\TrackView;
use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** Ringkasan booking untuk klien (BOOK-10). Dikirim lewat antrean agar halaman tidak menunggu. */
class BookingPlaced extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Booking $booking,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "Booking {$this->booking->code} diterima");
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.booking-placed', with: [
            'view' => TrackView::of($this->booking),
            'url' => route('track.show', $this->booking->access_token),
        ]);
    }
}
