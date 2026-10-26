<?php

namespace Tests\Feature;

use App\Mail\BookingPlaced;
use App\Models\Addon;
use App\Models\BlockedDate;
use App\Models\Booking;
use App\Models\Client;
use App\Models\Package;
use App\Models\Voucher;
use App\Support\Availability;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PublicBookingTest extends TestCase
{
    use RefreshDatabase;

    private string $date;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->date = today()->addDays(3)->toDateString();
    }

    private function payload(Package $package, array $override = []): array
    {
        return [
            'name' => 'Thalia Putri',
            'email' => 'thalia@example.com',
            'whatsapp' => '+62 812-3456-7890',
            'city' => 'Surabaya',
            'package_id' => $package->id,
            'session_date' => $this->date,
            'starts_at' => '09:00',
            'consent' => true,
        ] + $override;
    }

    public function test_booking_page_loads(): void
    {
        $this
            ->get('/')
            ->assertOk()
            ->assertInertia(fn(Assert $page) => $page->component('Booking/Create'));
    }

    public function test_client_books_with_addons_and_voucher(): void
    {
        $package = Package::factory()->create(['duration_minutes' => 120, 'price' => 750_000]);
        $addon = Addon::create(['name' => 'Cetak 10R', 'price' => 60_000, 'max_qty' => 5]);
        Voucher::create(['code' => 'WISUDA10', 'type' => 'percent', 'value' => 10]);

        $this->post('/booking', $this->payload($package, [
            'addons' => [['id' => $addon->id, 'qty' => 2]],
            'voucher' => 'wisuda10',
        ]))->assertRedirect();

        $booking = Booking::sole();
        $this->assertSame('081234567890', $booking->client->whatsapp);
        $this->assertSame('11:00', substr($booking->ends_at, 0, 5));
        $this->assertSame(87_000, $booking->discount);
        $this->assertSame(783_000, $booking->price()->total());
        $this->assertSame(1, Voucher::sole()->used);
        Mail::assertQueued(BookingPlaced::class);
    }

    public function test_checking_availability_does_not_use_up_the_booking_limit(): void
    {
        $package = Package::factory()->create(['duration_minutes' => 60]);

        foreach (range(1, 12) as $_) {
            $this->getJson("/availability?date={$this->date}&package={$package->id}")->assertOk();
        }

        $this->post('/booking', $this->payload($package))->assertRedirect();
    }

    public function test_public_booking_does_not_overwrite_existing_client(): void
    {
        $package = Package::factory()->create(['duration_minutes' => 60]);
        $this->post('/booking', $this->payload($package))->assertRedirect();

        $this->post('/booking', ['name' => 'Orang Lain', 'email' => 'lain@example.com', 'starts_at' => '13:00']
        + $this->payload($package))->assertSessionHasNoErrors();

        $this->assertSame(2, Booking::count());
        $this->assertSame('thalia@example.com', Booking::first()->client->email);
    }

    public function test_phone_format_is_the_same_from_admin_and_public_form(): void
    {
        $client = Client::factory()->create(['whatsapp' => '+62 812-3456-7890']);
        $this->assertSame('081234567890', $client->whatsapp);

        $this->post(
            '/booking',
            $this->payload(Package::factory()->create(['duration_minutes' => 60])),
        )->assertRedirect();

        $this->assertSame(1, Client::count());
    }

    public function test_full_slot_and_blocked_date_are_rejected(): void
    {
        $package = Package::factory()->create(['duration_minutes' => 60]);
        Booking::factory()->create(['session_date' => $this->date, 'starts_at' => '09:00', 'ends_at' => '10:00']);

        $this->post('/booking', $this->payload($package))->assertSessionHasErrors('starts_at');

        BlockedDate::create(['date' => $this->date]);
        $this->getJson("/availability?date={$this->date}&package={$package->id}")->assertExactJson(['times' => []]);

        $this->assertSame(1, Booking::count());
    }

    public function test_availability_lists_start_times_for_package_duration(): void
    {
        $package = Package::factory()->create(['duration_minutes' => 120]);

        $this
            ->getJson("/availability?date={$this->date}&package={$package->id}")
            ->assertJsonCount(9, 'times')
            ->assertJsonPath('times.0', '07:00');
    }

    public function test_admin_reschedule_check_ignores_the_booking_itself(): void
    {
        $booking = Booking::factory()->create([
            'session_date' => $this->date,
            'starts_at' => '09:00',
            'ends_at' => '10:00',
        ]);
        $date = Carbon::parse($this->date);

        $this->assertFalse(Availability::make()->fits($date, '09:30', 60));
        $this->assertTrue(Availability::make()->fits($date, '09:30', 60, ignore: $booking->id));
    }

    public function test_client_tracks_booking_with_code_and_phone(): void
    {
        $booking = Booking::factory()->create();

        $this->post('/lacak', [
            'code' => strtolower($booking->code),
            'phone' => substr($booking->client->whatsapp, -4),
        ])->assertRedirect(route('track.show', $booking->access_token));

        $this->get(route('track.show', $booking->access_token))->assertInertia(
            fn(Assert $page) => $page
                ->component('Track/Show')
                ->where('booking.code', $booking->code)
                ->where('booking.steps.0.state', 'current'),
        );
    }

    public function test_new_booking_shows_where_and_until_when_to_pay_deposit(): void
    {
        $booking = Booking::factory()->create();

        $this->get(route('track.show', $booking->access_token))->assertInertia(fn(Assert $page) => $page
            ->where('booking.deposit.amount', $booking->minDeposit())
            ->where('booking.deposit.accounts.0.number', config('aperta.payment.accounts.0.number'))
            ->where(
                'booking.deposit.whatsappUrl',
                fn(string $url) => (
                    str_contains($url, config('aperta.payment.whatsapp'))
                    && str_contains(urldecode($url), $booking->code)
                ),
            ));

        new BookingPlaced($booking)->assertSeeInHtml(config('aperta.payment.accounts.0.number'));

        $paid = Booking::factory()->paid()->create();
        $this->get(route('track.show', $paid->access_token))->assertInertia(
            fn(Assert $page) => $page->where('booking.deposit', null),
        );
    }

    public function test_tracking_locks_after_five_failed_attempts(): void
    {
        $booking = Booking::factory()->create();
        $valid = ['code' => $booking->code, 'phone' => substr($booking->client->whatsapp, -4)];

        foreach (range(1, 5) as $_) {
            $this->post('/lacak', ['code' => $booking->code, 'phone' => '0000'])->assertSessionHasErrors([
                'code' => 'Kode booking atau nomor tidak cocok.',
            ]);
        }

        $this->post('/lacak', $valid)->assertSessionHasErrors('code');
    }
}
