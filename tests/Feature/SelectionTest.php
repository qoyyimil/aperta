<?php

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SelectionTest extends TestCase
{
    use RefreshDatabase;

    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        $package = Package::factory()->create(['price' => 750_000, 'edit_quota' => 2, 'extra_photo_price' => 25_000]);
        $this->booking = Booking::factory()->forPackage($package)->paid()->create();
        foreach (range(1, 4) as $i) {
            $this->booking->proofs()->create(['frame' => $i, 'filename' => "DSC_{$i}.JPG", 'preview_path' => "p{$i}", 'thumb_path' => "t{$i}"]);
        }
        $this->booking->transitionTo(BookingStatus::Shot);
        $this->booking->publishProofs(2, 25_000, today()->addWeek()->toDateString());
    }

    private function url(string $suffix = ''): string
    {
        return "/lacak/{$this->booking->access_token}/foto{$suffix}";
    }

    public function test_gallery_is_hidden_until_proofs_are_published(): void
    {
        $other = Booking::factory()->create();

        $this->get("/lacak/{$other->access_token}/foto")->assertNotFound();
        $this->get($this->url())->assertInertia(fn (Assert $page) => $page
            ->component('Gallery/Select')
            ->where('booking.open', true)
            ->has('photos', 4));
    }

    public function test_client_selects_and_notes_photos_and_extras_are_charged(): void
    {
        [$a, $b, $c] = $this->booking->proofs()->orderBy('frame')->get();

        $this->patchJson($this->url("/{$a->id}"), ['selected' => true])->assertJsonPath('photo.selected', true);
        $this->patchJson($this->url("/{$b->id}"), ['selected' => true])->assertOk();
        $this->patchJson($this->url("/{$c->id}"), ['note' => 'Hapus orang di belakang'])->assertJsonPath('photo.selected', true);

        $this->assertSame(['selected' => 3, 'quota' => 2, 'extra' => 1, 'extraCost' => 25_000, 'notes' => 1], $this->booking->selectionSummary());
        $this->assertSame(775_000, $this->booking->fresh()->price()->total());
    }

    public function test_photo_of_another_booking_cannot_be_changed(): void
    {
        $foreign = Booking::factory()->create()->proofs()->create(['frame' => 1, 'filename' => 'X.JPG']);

        $this->patchJson($this->url("/{$foreign->id}"), ['selected' => true])->assertNotFound();
        $this->assertFalse($foreign->fresh()->is_selected);
    }

    public function test_submit_locks_selection_and_starts_editing(): void
    {
        $this->post($this->url('/kirim'))->assertSessionHasErrors('selection');

        $proof = $this->booking->proofs()->first();
        $proof->update(['is_selected' => true]);
        $this->post($this->url('/kirim'))->assertRedirect("/lacak/{$this->booking->access_token}");

        $this->booking->refresh();
        $this->assertSame(BookingStatus::Editing, $this->booking->status);
        $this->assertNotNull($this->booking->selection_submitted_at);
        $this->patchJson($this->url("/{$proof->id}"), ['selected' => false])->assertStatus(409);
        $this->get($this->url())->assertInertia(fn (Assert $page) => $page->where('booking.open', false));
    }
}
