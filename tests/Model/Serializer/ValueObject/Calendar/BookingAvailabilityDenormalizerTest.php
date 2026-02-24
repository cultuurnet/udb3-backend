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
        $this->assertNull($result->getCapacity());
        $this->assertNull($result->getRemainingCapacity());
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
        $this->assertNull($result->getCapacity());
        $this->assertNull($result->getRemainingCapacity());
    }

    /**
     * @test
     */
    public function it_derives_available_type_from_positive_remainingCapacity(): void
    {
        $result = $this->denormalizer->denormalize(
            ['remainingCapacity' => 42],
            BookingAvailability::class
        );

        $this->assertEquals(BookingAvailabilityType::Available(), $result->getType());
        $this->assertNull($result->getCapacity());
        $this->assertSame(42, $result->getRemainingCapacity());
    }

    /**
     * @test
     */
    public function it_derives_unavailable_type_from_zero_remainingCapacity(): void
    {
        $result = $this->denormalizer->denormalize(
            ['remainingCapacity' => 0],
            BookingAvailability::class
        );

        $this->assertEquals(BookingAvailabilityType::Unavailable(), $result->getType());
        $this->assertNull($result->getCapacity());
        $this->assertSame(0, $result->getRemainingCapacity());
    }

    /**
     * @test
     */
    public function it_denormalizes_capacity_and_remainingCapacity_together(): void
    {
        $result = $this->denormalizer->denormalize(
            ['capacity' => 100, 'remainingCapacity' => 42],
            BookingAvailability::class
        );

        $this->assertEquals(BookingAvailabilityType::Available(), $result->getType());
        $this->assertSame(100, $result->getCapacity());
        $this->assertSame(42, $result->getRemainingCapacity());
    }

    /**
     * @test
     */
    public function it_denormalizes_capacity_without_remainingCapacity(): void
    {
        $result = $this->denormalizer->denormalize(
            ['type' => 'Available', 'capacity' => 100],
            BookingAvailability::class
        );

        $this->assertEquals(BookingAvailabilityType::Available(), $result->getType());
        $this->assertSame(100, $result->getCapacity());
        $this->assertNull($result->getRemainingCapacity());
    }

    /**
     * @test
     */
    public function it_supports_denormalization_of_booking_remainingCapacity(): void
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
