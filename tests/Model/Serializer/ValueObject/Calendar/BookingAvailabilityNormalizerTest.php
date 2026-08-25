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
}
