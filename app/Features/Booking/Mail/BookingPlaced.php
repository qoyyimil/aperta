<?php

namespace App\Features\Booking\Mail;

use App\Features\Tracking\Http\TrackView;
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
        $view = TrackView::of($this->booking);
        foreach (['name', 'location'] as $typedByClient) {
            $view[$typedByClient] = self::plainText((string) $view[$typedByClient]);
        }

        return new Content(markdown: 'mail.booking-placed', with: [
            'view' => $view,
            'url' => route('track.show', $this->booking->access_token),
        ]);
    }

    /** Isian klien tampil apa adanya, tidak menjadi tautan atau format Markdown. */
    private static function plainText(string $text): string
    {
        return addcslashes($text, '\\`*_[]()!|~#');
    }
}
