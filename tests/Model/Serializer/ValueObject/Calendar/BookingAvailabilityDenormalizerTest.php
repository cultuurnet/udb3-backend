<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use PHPUnit\Framework\TestCase;

final class BookingAvailabilityDenormalizerTest extends TestCase
{
    private BookingAvailabilityDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new BookingAvailabilityDenormalizer();
    }

    /**
     * @test
     */
    public function it_denormalizes_with_explicit_type(): void
    {
        $result = $this->denormalizer->denormalize(
            ['type' => 'Available'],
            BookingAvailability::class
        );

        $this->assertEquals(BookingAvailabilityType::Available(), $result->getType());
    }

    /**
     * @test
     */
    public function it_denormalizes_with_explicit_unavailable_type(): void
    {
        $result = $this->denormalizer->denormalize(
            ['type' => 'Unavailable'],
            BookingAvailability::class
        );

        $this->assertEquals(BookingAvailabilityType::Unavailable(), $result->getType());
    }

    /**
     * The capacity and remainingCapacity properties were removed, so any value that is still sent
     * has to be ignored instead of validated.
     *
     * @see https://jira.publiq.be/browse/III-7392
     *
     * @test
     */
    public function it_ignores_capacity_and_remaining_capacity(): void
    {
        $result = $this->denormalizer->denormalize(
            ['type' => 'Available', 'capacity' => 10, 'remainingCapacity' => 0],
            BookingAvailability::class
        );

        $this->assertEquals(BookingAvailabilityType::Available(), $result->getType());
    }

    /**
     * @test
     */
    public function it_supports_denormalization_of_booking_availability(): void
    {
        $this->assertTrue(
            $this->denormalizer->supportsDenormalization([], BookingAvailability::class)
        );
    }

    /**
     * @test
     */
    public function it_does_not_support_denormalization_of_other_types(): void
    {
        $this->assertFalse(
            $this->denormalizer->supportsDenormalization([], \stdClass::class)
        );
    }
}
