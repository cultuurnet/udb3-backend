<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
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
    public function it_normalizes_with_a_waiting_list_url(): void
    {
        $this->assertSame(
            ['type' => 'Unavailable', 'waitingListUrl' => 'https://publiq.be/wachtlijst'],
            $this->normalizer->normalize(
                BookingAvailability::Unavailable()->withWaitingListUrl(new Url('https://publiq.be/wachtlijst'))
            )
        );
    }
}
