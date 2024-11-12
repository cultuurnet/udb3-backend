<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability as Udb3ModelBookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use PHPUnit\Framework\TestCase;

final class BookingAvailabilityTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $this->assertEquals(
            ['type' => 'Available'],
            BookingAvailability::available()->serialize()
        );

        $this->assertEquals(
            ['type' => 'Unavailable'],
            BookingAvailability::unavailable()->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_be_deserialized(): void
    {
        $this->assertEquals(
            new BookingAvailability(new BookingAvailabilityType('Available')),
            BookingAvailability::deserialize(['type' => 'Available'])
        );

        $this->assertEquals(
            new BookingAvailability(new BookingAvailabilityType('Unavailable')),
            BookingAvailability::deserialize(['type' => 'Unavailable'])
        );
    }

    /**
     * @test
     */
    public function it_is_comparable(): void
    {
        $this->assertTrue(
            BookingAvailability::available()->equals(
                new BookingAvailability(BookingAvailabilityType::Available())
            )
        );

        $this->assertTrue(
            BookingAvailability::unavailable()->equals(
                new BookingAvailability(BookingAvailabilityType::Unavailable())
            )
        );

        $this->assertFalse(
            BookingAvailability::available()->equals(
                new BookingAvailability(BookingAvailabilityType::Unavailable())
            )
        );

        $this->assertFalse(
            BookingAvailability::unavailable()->equals(
                new BookingAvailability(BookingAvailabilityType::Available())
            )
        );
    }

    /**
     * @test
     */
    public function it_can_be_created_from_an_imported_udb3_model_value(): void
    {
        $bookingAvailability = BookingAvailability::fromUdb3ModelBookingAvailability(
            new Udb3ModelBookingAvailability(BookingAvailabilityType::Unavailable())
        );

        $this->assertTrue(BookingAvailability::unavailable()->equals($bookingAvailability));
    }
}
