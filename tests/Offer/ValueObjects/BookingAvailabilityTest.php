<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ValueObjects;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability as Udb3ModelBookingAvailability;
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
            BookingAvailability::fromNative('Available'),
            BookingAvailability::deserialize(['type' => 'Available'])
        );

        $this->assertEquals(
            BookingAvailability::fromNative('Unavailable'),
            BookingAvailability::deserialize(['type' => 'Unavailable'])
        );
    }

    /**
     * @test
     */
    public function it_is_comparable(): void
    {
        $this->assertTrue(
            BookingAvailability::available()->equals(BookingAvailability::fromNative('Available'))
        );

        $this->assertFalse(
            BookingAvailability::fromNative('Unavailable')->equals(BookingAvailability::available())
        );
    }

    /**
     * @test
     */
    public function it_can_be_created_from_an_imported_udb3_model_value(): void
    {
        $bookingAvailability = BookingAvailability::fromUdb3ModelBookingAvailability(
            Udb3ModelBookingAvailability::Unavailable()
        );

        $this->assertTrue(BookingAvailability::unavailable()->equals($bookingAvailability));
    }
}
