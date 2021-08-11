<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ValueObjects;

use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use PHPUnit\Framework\TestCase;

final class SubEventUpdateTest extends TestCase
{
    /**
     * @test
     */
    public function it_requires_a_sub_event_index(): void
    {
        $subEventIndex = 3;
        $subEventUpdate = new SubEventUpdate($subEventIndex);

        $this->assertEquals($subEventIndex, $subEventUpdate->getSubEventIndex());
    }

    /**
     * @test
     */
    public function it_has_an_optional_status(): void
    {
        $status = new Status(StatusType::temporarilyUnavailable(), []);
        $subEventUpdate = (new SubEventUpdate(3))->withStatus($status);

        $this->assertEquals($status, $subEventUpdate->getStatus());
    }

    /**
     * @test
     */
    public function it_has_an_optional_booking_availability(): void
    {
        $bookingAvailability = BookingAvailability::unavailable();
        $subEventUpdate = (new SubEventUpdate(3))->withBookingAvailability($bookingAvailability);

        $this->assertEquals($bookingAvailability, $subEventUpdate->getBookingAvailability());
    }
}
