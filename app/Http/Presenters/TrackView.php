<?php

namespace App\Http\Presenters;

use App\Enums\BookingStatus as S;
use App\Models\Booking;
use App\Support\Rupiah;

/** Data booking yang aman ditampilkan ke klien di halaman lacak (TRK-5 s.d. TRK-8). */
final class TrackView
{
    /** Judul => keterangan singkat tiap tahap. */
    private const STEPS = [
        'Booking diterima' => 'Data dan jadwal tercatat',
        'Terkonfirmasi' => 'DP diterima, lunas H-1 sesi',
        'Sesi foto' => 'Pemotretan di lokasi',
        'Pilih foto' => 'Pilih foto untuk diedit',
        'Editing' => 'Foto pilihanmu diedit',
        'Hasil dikirim' => 'Unduh hasil akhir',
    ];
    public static function of(Booking $b): array
    {
        $b->loadMissing('client', 'addons');
        $price = $b->price();

        return [
            'code' => $b->code,
            'name' => $b->client->name,
            'service' => $b->service,
            'date' => $b->session_date->translatedFormat('l, d F Y'),
            'time' => substr($b->starts_at, 0, 5) . '–' . substr($b->ends_at, 0, 5),
            'location' => collect([$b->location, $b->institution, $b->city])->filter()->join(', '),
            'package' => $b->package_name,
            'addons' => $b->addons->map(fn ($a) => ['name' => $a->name, 'qty' => $a->pivot->qty, 'total' => $a->pivot->qty * $a->pivot->price]),
            'price' => [...get_object_vars($price), 'total' => $price->total()],
            'paid' => $b->paid(),
            'payment' => $b->paymentStatus()->getLabel(),
            'cancelled' => $b->status === S::Cancelled,
            'cancelReason' => $b->cancel_reason,
            'gallery' => $b->status->hasGallery() ? [
                'url' => route('selection.show', $b->access_token),
                'open' => $b->status->selectionOpen(),
            ] : null,
            'resultsUrl' => $b->status === S::Delivered ? route('results.show', $b->access_token) : null,
            'steps' => self::steps($b->status),
            'next' => self::next($b),
        ];
    }

    private static function steps(S $status): array
    {
        $flow = array_values(array_filter(S::cases(), fn (S $s) => $s !== S::Cancelled));
        $current = array_search($status, $flow, true);

        return array_map(fn (string $label, string $content, int $i) => [
            'label' => $label,
            'content' => $content,
            'state' => match (true) {
                $current === false => 'todo',
                $i < $current, $status === S::Delivered => 'done',
                $i === $current => 'current',
                default => 'todo',
            },
        ], array_keys(self::STEPS), self::STEPS, range(0, count(self::STEPS) - 1));
    }

    private static function next(Booking $b): string
    {
        return match ($b->status) {
            S::New => 'Bayar DP minimal ' . Rupiah::format($b->minDeposit()) . ' agar jadwal terkunci. Admin akan mengonfirmasi setelah pembayaran diterima.',
            S::Confirmed => $b->isSessionPaid()
                ? "Sampai jumpa di sesi foto pada {$b->session_date->translatedFormat('d F Y')} pukul " . substr($b->starts_at, 0, 5) . '.'
                : 'Lunasi sisa tagihan ' . Rupiah::format($b->sessionBalance()) . ' paling lambat ' . $b->paymentDueOn()->translatedFormat('d F Y') . ' agar sesi bisa berjalan.',
            S::Shot => 'Fotomu sedang disortir. Kamu akan dikabari saat foto siap dipilih.',
            S::Selecting => 'Pilih foto yang ingin diedit sebelum ' . $b->selection_deadline?->translatedFormat('d F Y') . '.',
            S::Editing => 'Foto pilihanmu sedang diedit.',
            S::Delivered => $b->canDownloadResults()
                ? 'Hasil edit sudah siap. Lihat dan unduh lewat tombol di samping.'
                : 'Hasil edit sudah siap dilihat. Unduhan resolusi penuh terbuka setelah sisa tagihan dilunasi.',
            S::Cancelled => 'Booking ini dibatalkan. Hubungi admin jika ada pertanyaan.',
        };
    }
}
