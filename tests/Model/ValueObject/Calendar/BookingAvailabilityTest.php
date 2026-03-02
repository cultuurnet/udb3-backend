<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use PHPUnit\Framework\TestCase;

final class BookingAvailabilityTest extends TestCase
{
    /**
     * @test
     */
    public function it_throws_when_remaining_capacity_exceeds_capacity_via_with_remaining_capacity(): void
    {
        $bookingAvailability = BookingAvailability::Available()
            ->withCapacity(10);

        $this->expectException(RemainingCapacityExceedsCapacity::class);
        $this->expectExceptionMessage('/bookingAvailability/remainingCapacity: remainingCapacity must be less than or equal to capacity');

        $bookingAvailability->withRemainingCapacity(99);
    }

    /**
     * @test
     */
    public function it_throws_when_remaining_capacity_exceeds_capacity_via_with_capacity(): void
    {
        $bookingAvailability = BookingAvailability::Available()
            ->withRemainingCapacity(99);

        $this->expectException(RemainingCapacityExceedsCapacity::class);
        $this->expectExceptionMessage('/bookingAvailability/remainingCapacity: remainingCapacity must be less than or equal to capacity');

        $bookingAvailability->withCapacity(10);
    }

    /**
     * @test
     */
    public function it_allows_remaining_capacity_equal_to_capacity(): void
    {
        $bookingAvailability = BookingAvailability::Available()
            ->withCapacity(100)
            ->withRemainingCapacity(100);

        $this->assertSame(100, $bookingAvailability->getCapacity());
        $this->assertSame(100, $bookingAvailability->getRemainingCapacity());
    }

    /**
     * @test
     */
    public function it_allows_remaining_capacity_less_than_capacity(): void
    {
        $bookingAvailability = BookingAvailability::Available()
            ->withCapacity(100)
            ->withRemainingCapacity(42);

        $this->assertSame(100, $bookingAvailability->getCapacity());
        $this->assertSame(42, $bookingAvailability->getRemainingCapacity());
    }

    /**
     * @test
     */
    public function it_allows_setting_capacity_without_remaining_capacity(): void
    {
        $bookingAvailability = BookingAvailability::Available()
            ->withCapacity(100);

        $this->assertSame(100, $bookingAvailability->getCapacity());
        $this->assertNull($bookingAvailability->getRemainingCapacity());
    }

    /**
     * @test
     */
    public function it_allows_setting_remaining_capacity_without_capacity(): void
    {
        $bookingAvailability = BookingAvailability::Available()
            ->withRemainingCapacity(50);

        $this->assertNull($bookingAvailability->getCapacity());
        $this->assertSame(50, $bookingAvailability->getRemainingCapacity());
    }

    /**
     * @test
     */
    public function it_allows_zero_remaining_capacity(): void
    {
        $bookingAvailability = BookingAvailability::Available()
            ->withCapacity(100)
            ->withRemainingCapacity(0);

        $this->assertSame(100, $bookingAvailability->getCapacity());
        $this->assertSame(0, $bookingAvailability->getRemainingCapacity());
    }
}
