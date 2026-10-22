<?php

namespace Tests\Unit;

use App\Support\SlotGrid;
use PHPUnit\Framework\TestCase;

class SlotGridTest extends TestCase
{
    private function grid(int $capacity = 1): SlotGrid
    {
        return new SlotGrid('07:00', '17:00', 60, $capacity);
    }

    public function test_long_sessions_need_consecutive_free_slots(): void
    {
        $times = $this->grid()->startTimes(120);

        $this->assertSame('07:00', $times[0]);
        $this->assertSame('15:00', end($times));
        $this->assertCount(9, $times);
    }

    public function test_booked_slot_blocks_overlapping_starts_only(): void
    {
        $times = $this->grid()->startTimes(120, [['09:00', '10:00']]);

        $this->assertNotContains('08:00', $times);
        $this->assertNotContains('09:00', $times);
        $this->assertContains('07:00', $times);
        $this->assertContains('10:00', $times);
    }

    public function test_capacity_allows_parallel_sessions(): void
    {
        $booked = [['09:00', '11:00']];

        $this->assertFalse($this->grid(1)->allows('09:00', 60, $booked));
        $this->assertTrue($this->grid(2)->allows('09:00', 60, $booked));
        $this->assertFalse($this->grid(2)->allows('09:00', 60, [...$booked, ['10:00', '11:00'], ['09:00', '10:00']]));
    }

    public function test_client_must_start_on_a_slot_but_admin_may_not(): void
    {
        $this->assertFalse($this->grid()->allows('09:30', 60));
        $this->assertFalse($this->grid()->allows('16:30', 60));
        $this->assertTrue($this->grid()->fits('09:30', 60, [['08:00', '09:00']]));
        $this->assertFalse($this->grid()->fits('09:30', 60, [['10:00', '11:00']]));
    }
}
