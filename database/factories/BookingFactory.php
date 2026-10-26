<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Client;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'session_date' => now()->addWeek(),
            'starts_at' => '09:00',
            'ends_at' => '11:00',
            'city' => 'Surabaya',
        ];
    }

    /** Booking selalu memakai salinan paket, sama seperti di aplikasi. */
    public function configure(): static
    {
        return $this->afterMaking(fn(Booking $b) => $b->package_name ?? $b->applyPackage(Package::factory()->create()));
    }

    /** Tagihan sesi dibayar penuh, sehingga otomatis terkonfirmasi dan boleh ditandai sesi selesai. */
    public function paid(): static
    {
        return $this->afterCreating(function (Booking $b) {
            $b->payments()->create([
                'amount' => $b->price()->sessionTotal(),
                'paid_on' => today(),
                'method' => 'transfer',
            ]);
            $b->refresh(); // status sudah Confirmed di database, segarkan objek ini
        });
    }

    public function forPackage(Package $p): static
    {
        return $this->state($p->snapshot());
    }
}
