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
    public function it_normalizes_with_capacity_and_availability(): void
    {
        $bookingAvailability = BookingAvailability::Available()
            ->withCapacity(100)
            ->withAvailability(42);

        $this->assertSame(
            ['type' => 'Available', 'capacity' => 100, 'availability' => 42],
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
    public function it_omits_null_capacity_and_availability(): void
    {
        $bookingAvailability = BookingAvailability::Available()
            ->withCapacity(null)
            ->withAvailability(null);

        $this->assertSame(
            ['type' => 'Available'],
            $this->normalizer->normalize($bookingAvailability)
        );
    }

    /**
     * @test
     */
    public function it_normalizes_zero_availability(): void
    {
        $bookingAvailability = BookingAvailability::Unavailable()
            ->withCapacity(100)
            ->withAvailability(0);

        $this->assertSame(
            ['type' => 'Unavailable', 'capacity' => 100, 'availability' => 0],
            $this->normalizer->normalize($bookingAvailability)
        );
    }
}
