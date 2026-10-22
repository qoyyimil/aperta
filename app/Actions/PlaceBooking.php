<?php

namespace App\Actions;

use App\Exceptions\SlotUnavailable;
use App\Models\Addon;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Package;
use App\Models\Voucher;
use App\Support\Availability;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Membuat booking dari portal klien (fitur BOOK). */
final class PlaceBooking
{
    public function handle(array $data): Booking
    {
        $package = Package::active()->findOrFail($data['package_id']);
        $date = Carbon::parse($data['session_date']);

        // Kunci per tanggal: dua klien di slot yang sama diproses bergantian (BOOK-8).
        return Cache::lock("booking-slot:{$date->toDateString()}", 10)->block(5, function () use ($data, $package, $date) {
            throw_unless(
                Availability::make()->allows($date, $data['starts_at'], $package->duration_minutes),
                SlotUnavailable::class,
            );

            return DB::transaction(fn () => $this->create($data, $package, $date));
        });
    }

    private function create(array $data, Package $package, Carbon $date): Booking
    {
        // Data klien lama tidak ditimpa dari form publik.
        $client = Client::firstOrCreate(
            ['whatsapp' => Client::normalizePhone($data['whatsapp'])],
            Arr::only($data, ['name', 'email', 'instagram']),
        );

        $booking = new Booking(Arr::only($data, ['session_date', 'starts_at', 'city', 'location', 'lat', 'lng', 'institution', 'notes']));
        $booking->applyPackage($package)->fill([
            'client_id' => $client->id,
            'ends_at' => $date->copy()->setTimeFromTimeString($data['starts_at'])->addMinutes($package->duration_minutes)->format('H:i'),
        ])->save();

        $this->attachAddons($booking, $data['addons'] ?? []);
        $this->applyVoucher($booking, $data['voucher'] ?? null);

        return $booking;
    }

    /** Harga add-on disalin saat booking; jumlah dibatasi max_qty. */
    private function attachAddons(Booking $booking, array $items): void
    {
        $addons = Addon::active()->findMany(array_column($items, 'id'))->keyBy('id');

        $booking->addons()->attach(collect($items)
            ->filter(fn (array $i) => $addons->has($i['id']))
            ->mapWithKeys(fn (array $i) => [$i['id'] => [
                'qty' => min((int) $i['qty'], $addons[$i['id']]->max_qty),
                'price' => $addons[$i['id']]->price,
            ]]));
    }

    private function applyVoucher(Booking $booking, ?string $code): void
    {
        if (blank($code)) {
            return;
        }

        $voucher = Voucher::where('code', strtoupper(trim($code)))->lockForUpdate()->first();
        $problem = $voucher ? $voucher->rejection() : 'Kode voucher tidak ditemukan.';
        if ($problem) {
            throw ValidationException::withMessages(['voucher' => $problem]);
        }

        $booking->forceFill([
            'voucher_code' => $voucher->code,
            'discount' => $voucher->discountFor($booking->price()->subtotal()),
        ])->save();
        $voucher->increment('used');
    }
}
