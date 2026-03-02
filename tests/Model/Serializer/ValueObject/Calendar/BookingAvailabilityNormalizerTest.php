<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use PHPUnit\Framework\TestCase;

final class BookingAvailabilityNormalizerTest extends TestCase
{
    private BookingAvailabilityNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new BookingAvailabilityNormalizer();
    }

    /**
     * @test
     */
    public function it_normalizes_with_only_type(): void
    {
        $this->assertSame(
            ['type' => 'Available'],
            $this->normalizer->normalize(BookingAvailability::Available())
        );
    }

    /**
     * @test
     */
    public function it_normalizes_with_capacity_and_remainingCapacity(): void
    {
        $bookingAvailability = BookingAvailability::Available()
            ->withCapacity(100)
            ->withRemainingCapacity(42);

        $this->assertSame(
            ['type' => 'Available', 'capacity' => 100, 'remainingCapacity' => 42],
            $this->normalizer->normalize($bookingAvailability)
        );
    }

    /**
     * @test
     */
    public function it_normalizes_with_capacity_only(): void
    {
        $bookingAvailability = BookingAvailability::Available()->withCapacity(100);

        $this->assertSame(
            ['type' => 'Available', 'capacity' => 100],
            $this->normalizer->normalize($bookingAvailability)
        );
    }

    /**
     * @test
     */
    public function it_omits_null_capacity_and_remainingCapacity(): void
    {
        $bookingAvailability = BookingAvailability::Available();

        $this->assertSame(
            ['type' => 'Available'],
            $this->normalizer->normalize($bookingAvailability)
        );
    }

    /**
     * @test
     */
    public function it_normalizes_zero_remainingCapacity(): void
    {
        $bookingAvailability = BookingAvailability::Unavailable()
            ->withCapacity(100)
            ->withRemainingCapacity(0);

        $this->assertSame(
            ['type' => 'Unavailable', 'capacity' => 100, 'remainingCapacity' => 0],
            $this->normalizer->normalize($bookingAvailability)
        );
    }
}
