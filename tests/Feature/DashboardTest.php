<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\UserRole;
use App\Filament\Widgets\AgendaWidget;
use App\Filament\Widgets\FinanceStatsWidget;
use App\Filament\Widgets\MyQueueWidget;
use App\Models\Booking;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-05 10:00');
    }

    private function user(UserRole $role): User
    {
        return User::factory()->create(['role' => $role]);
    }

    private function booking(BookingStatus $status, array $attrs = [], ?User $crew = null): Booking
    {
        $booking = Booking::factory()->create(['status' => $status] + $attrs);
        $crew && $booking->crew()->attach($crew);

        return $booking;
    }

    /** Proof bernomor; $selected pertama dipilih, $notes pertama diberi catatan. */
    private function proofs(Booking $booking, int $count, int $selected = 0, int $notes = 0): void
    {
        foreach (range(1, $count) as $i) {
            $booking->proofs()->create([
                'frame' => $i, 'filename' => "DSC_{$i}.jpg",
                'is_selected' => $i <= $selected, 'note' => $i <= $notes ? 'Rapikan rambut' : null,
            ]);
        }
    }

    private function reason(Booking $booking): ?string
    {
        return Booking::withBilling()->findOrFail($booking->id)->actionReason();
    }

    public function test_finance_widget_is_admin_only(): void
    {
        $this->booking(BookingStatus::New, ['session_date' => '2026-11-07']);
        $headings = [UserRole::Admin->value => 'Perlu tindakan', UserRole::Photographer->value => 'Belum diunggah', UserRole::Editor->value => 'Antrean edit'];

        foreach (UserRole::cases() as $role) {
            $this->actingAs($this->user($role))->get('/studio')->assertOk();

            Livewire::test(MyQueueWidget::class)->assertSee($headings[$role->value]);
            Livewire::test(AgendaWidget::class)->assertSee('Agenda 7 hari');
            $this->assertTrue(MyQueueWidget::canView() && AgendaWidget::canView());
            $this->assertSame($role === UserRole::Admin, FinanceStatsWidget::canView());
        }
    }

    public function test_photographer_only_sees_assigned_bookings(): void
    {
        $me = $this->user(UserRole::Photographer);
        $mine = $this->booking(BookingStatus::Shot, crew: $me);
        $other = $this->booking(BookingStatus::Shot);

        $this->assertEquals([$mine->id], Booking::visibleTo($me)->pluck('id')->all());
        $this->assertCount(2, Booking::visibleTo($this->user(UserRole::Admin))->get());

        $this->actingAs($me);
        Livewire::test(MyQueueWidget::class)->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$other]);
        Livewire::test(AgendaWidget::class)->assertCanSeeTableRecords([$mine])->assertCanNotSeeTableRecords([$other]);
    }

    public function test_needs_action_picks_unpaid_due_and_open_work(): void
    {
        $new = $this->booking(BookingStatus::New);
        $due = $this->booking(BookingStatus::Confirmed, ['session_date' => '2026-11-07']);
        $shot = $this->booking(BookingStatus::Shot);
        $deadline = $this->booking(BookingStatus::Selecting, ['selection_deadline' => '2026-11-06']);
        $paidFar = Booking::factory()->paid()->create(['session_date' => '2026-11-30']);
        $this->booking(BookingStatus::Selecting, ['selection_deadline' => '2026-11-20']);

        $this->assertSame(BookingStatus::Confirmed, $paidFar->status);
        $this->assertEqualsCanonicalizing(
            [$new->id, $due->id, $shot->id, $deadline->id],
            Booking::needsAction()->pluck('id')->all(),
        );
    }

    public function test_photographer_queue_only_lists_sessions_without_proofs(): void
    {
        $me = $this->user(UserRole::Photographer);
        $empty = $this->booking(BookingStatus::Shot, crew: $me);
        $uploaded = $this->booking(BookingStatus::Shot, crew: $me);
        $this->proofs($uploaded, 1);

        $this->actingAs($me);
        Livewire::test(MyQueueWidget::class)
            ->assertCanSeeTableRecords([$empty])
            ->assertCanNotSeeTableRecords([$uploaded]);
    }

    public function test_editor_queue_starts_with_longest_waiting(): void
    {
        $editor = $this->user(UserRole::Editor);
        $newer = $this->booking(BookingStatus::Editing, ['selection_submitted_at' => now()->subDay()], $editor);
        $older = $this->booking(BookingStatus::Editing, ['selection_submitted_at' => now()->subDays(4)], $editor);
        $foreign = $this->booking(BookingStatus::Editing, ['selection_submitted_at' => now()->subDays(9)]);

        $this->actingAs($editor);
        Livewire::test(MyQueueWidget::class)
            ->assertCanSeeTableRecords([$older, $newer], inOrder: true)
            ->assertCanNotSeeTableRecords([$foreign]);
    }

    public function test_action_reason_per_case(): void
    {
        $uploaded = $this->booking(BookingStatus::Shot);
        $this->proofs($uploaded, 2);
        $editing = $this->booking(BookingStatus::Editing);
        $this->proofs($editing, 5, selected: 3, notes: 1);
        $editing->results()->create(['filename' => 'DSC_1_edit.jpg', 'path' => 'x', 'size' => 1,
            'proof_photo_id' => $editing->proofs()->where('frame', 1)->value('id')]);

        $cases = [
            'Belum DP' => $this->booking(BookingStatus::New),
            'Lunas jatuh tempo 7 Nov' => $this->booking(BookingStatus::Confirmed, ['session_date' => '2026-11-08']),
            'Lewat jatuh tempo 4 Nov' => $this->booking(BookingStatus::Confirmed, ['session_date' => '2026-11-05']),
            'Proof belum diunggah' => $this->booking(BookingStatus::Shot),
            'Galeri belum dibuka' => $uploaded,
            'Batas pilih 2 hari lagi' => $this->booking(BookingStatus::Selecting, ['selection_deadline' => '2026-11-07']),
            'Batas pilih hari ini' => $this->booking(BookingStatus::Selecting, ['selection_deadline' => '2026-11-05']),
            'Batas pilih lewat 1 hari' => $this->booking(BookingStatus::Selecting, ['selection_deadline' => '2026-11-04']),
            '3 foto, 1 catatan, 2 belum ada hasil' => $editing,
        ];

        foreach ($cases as $expected => $booking) {
            $this->assertSame($expected, $this->reason($booking));
        }
    }

    public function test_finance_numbers_match_payments_and_balances(): void
    {
        $package = Package::factory()->create(['price' => 1_000_000, 'edit_quota' => 2, 'extra_photo_price' => 50_000]);
        $make = fn (string $date, BookingStatus $status = BookingStatus::New) => Booking::factory()
            ->forPackage($package)->create(['session_date' => $date, 'status' => $status]);

        $deposit = $make('2026-11-12');
        $deposit->payments()->create(['amount' => 400_000, 'paid_on' => '2026-11-03', 'method' => 'cash']);
        $extras = $make('2026-11-20');
        $extras->payments()->create(['amount' => 1_000_000, 'paid_on' => '2026-10-30', 'method' => 'transfer']);
        $this->proofs($extras, 3, selected: 3);
        $make('2026-12-02');
        $make('2026-11-10', BookingStatus::Cancelled);

        // 600rb sisa DP + 50rb foto tambahan + 1jt belum bayar; booking batal tidak dihitung.
        $this->assertSame(1_650_000, Booking::receivables());
        $this->assertSame(2, Booking::thisMonth()->count());

        $this->actingAs($this->user(UserRole::Admin));
        Livewire::test(FinanceStatsWidget::class)
            ->assertSee('Rp400.000')
            ->assertSee('Rp1.650.000');
    }
}
