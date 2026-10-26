<?php

namespace App\Http\Controllers;

use App\Http\Presenters\GalleryView;
use App\Models\Booking;
use App\Models\ProofPhoto;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Klien memilih foto yang ingin diedit (fitur SEL). Akses lewat token booking, tanpa login. */
class SelectionController extends Controller
{
    public function show(Booking $booking): Response
    {
        abort_unless($booking->status->hasGallery(), 404);

        return Inertia::render('Gallery/Select', GalleryView::of($booking));
    }

    /** Simpan satu perubahan: pilih/batal pilih atau catatan (SEL-2, SEL-6). */
    public function update(Request $request, Booking $booking, ProofPhoto $proof): JsonResponse
    {
        $this->ensureOpen($booking);
        $data = $request->validate([
            'selected' => ['sometimes', 'boolean'],
            'note' => ['sometimes', 'nullable', 'string', 'max:300'],
        ]);

        if ($request->has('note')) {
            $proof->annotate($data['note']);
        }
        if ($request->has('selected')) {
            $proof->update(['is_selected' => $data['selected']]);
        }

        return response()->json(['photo' => GalleryView::photo($proof)]);
    }

    public function submit(Booking $booking): RedirectResponse
    {
        $this->ensureOpen($booking);

        try {
            $booking->submitSelection();
        } catch (DomainException $e) {
            return back()->withErrors(['selection' => $e->getMessage()]);
        }

        return to_route('track.show', $booking->access_token)->with(
            'success',
            'Pilihan terkirim! Fotografer akan mulai mengedit foto pilihanmu.',
        );
    }

    private function ensureOpen(Booking $booking): void
    {
        abort_unless(
            $booking->status->selectionOpen(),
            409,
            'Pilihan sudah dikirim dan dikunci. Hubungi admin jika ingin mengubah.',
        );
    }
}
