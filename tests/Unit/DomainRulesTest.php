<?php

namespace Tests\Unit;

use App\Enums\BookingStatus as S;
use App\Enums\DiscountType;
use App\Enums\PaymentStatus;
use App\Support\BookingCode;
use App\Support\PriceBreakdown;
use PHPUnit\Framework\TestCase;

class DomainRulesTest extends TestCase
{
    public function test_status_follows_the_booking_lifecycle(): void
    {
        $this->assertTrue(S::New->canTransitionTo(S::Confirmed));
        $this->assertTrue(S::Editing->canTransitionTo(S::Selecting));
        $this->assertFalse(S::New->canTransitionTo(S::Editing));
        $this->assertFalse(S::Delivered->canTransitionTo(S::Cancelled));
        $this->assertSame([], S::Cancelled->transitions());
    }

    public function test_selection_is_only_open_while_selecting(): void
    {
        $open = array_filter(S::cases(), fn(S $s) => $s->selectionOpen());

        $this->assertSame([S::Selecting], array_values($open));
    }

    public function test_payment_status_from_amounts(): void
    {
        $this->assertSame(PaymentStatus::Unpaid, PaymentStatus::fromAmounts(0, 1_000_000, 500_000));
        $this->assertSame(PaymentStatus::Unpaid, PaymentStatus::fromAmounts(400_000, 1_000_000, 500_000));
        $this->assertSame(PaymentStatus::Deposit, PaymentStatus::fromAmounts(500_000, 1_000_000, 500_000));
        $this->assertSame(PaymentStatus::Paid, PaymentStatus::fromAmounts(1_000_000, 1_000_000, 500_000));
    }

    public function test_discount_never_exceeds_amount(): void
    {
        $this->assertSame(75_000, DiscountType::Percent->apply(10, 750_000));
        $this->assertSame(50_000, DiscountType::Fixed->apply(50_000, 750_000));
        $this->assertSame(30_000, DiscountType::Fixed->apply(50_000, 30_000));
    }

    public function test_price_breakdown_total(): void
    {
        $price = new PriceBreakdown(package: 750_000, addons: 65_000, extras: 75_000, discount: 50_000);

        $this->assertSame(890_000, $price->subtotal());
        $this->assertSame(840_000, $price->total());
        $this->assertSame(0, new PriceBreakdown(100, discount: 500)->total());
        $this->assertSame(765_000, $price->sessionTotal(), 'foto tambahan tidak ikut tagihan sesi');
    }

    public function test_booking_code_format_and_retry_on_collision(): void
    {
        $this->assertMatchesRegularExpression('/^AP-[A-HJ-NP-Z2-9]{6}$/', BookingCode::generate());

        $tries = 0;
        BookingCode::generate(function () use (&$tries) {
            return ++$tries < 3;
        });
        $this->assertSame(3, $tries);

        $this->assertSame('AP-ABC234', BookingCode::normalize('  ap-abc234 '));
    }
}
