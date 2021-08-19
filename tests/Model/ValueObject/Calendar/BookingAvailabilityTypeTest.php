<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use PHPUnit\Framework\TestCase;

final class BookingAvailabilityTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_supports_exactly_two_availabilities(): void
    {
        $availableBooking = BookingAvailabilityType::Available();
        $unavailableBooking = BookingAvailabilityType::Unavailable();

        $this->assertEquals('Available', $availableBooking->toString());
        $this->assertEquals('Unavailable', $unavailableBooking->toString());
    }
}
