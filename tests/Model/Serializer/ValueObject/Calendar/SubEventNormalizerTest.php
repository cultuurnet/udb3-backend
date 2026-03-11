<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SubEventNormalizerTest extends TestCase
{
    private SubEventNormalizer $normalizer;

    private SubEvent $subEvent;

    protected function setUp(): void
    {
        $this->normalizer = new SubEventNormalizer();

        $this->subEvent = new SubEvent(
            new DateRange(
                new DateTimeImmutable('2021-05-17T16:00:00+00:00'),
                new DateTimeImmutable('2021-05-17T22:00:00+00:00')
            ),
            new Status(StatusType::Available()),
            new BookingAvailability(BookingAvailabilityType::Available()),
            new BookingInfo()
        );
    }

    /**
     * @test
     */
    public function it_does_not_include_childcare_when_not_set(): void
    {
        $normalized = $this->normalizer->normalize($this->subEvent);

        $this->assertArrayNotHasKey('childcare', $normalized);
    }

    /**
     * @test
     */
    public function it_includes_nested_childcare_object_when_set(): void
    {
        $subEvent = $this->subEvent->withChildcareTimeRange(new TimeImmutableRange('15:00', '23:00'));
        $normalized = $this->normalizer->normalize($subEvent);

        $this->assertSame('15:00', $normalized['childcare']['start']);
        $this->assertSame('23:00', $normalized['childcare']['end']);
    }

    /**
     * @test
     */
    public function it_includes_childcare_with_null_end_when_only_start_is_set(): void
    {
        $subEvent = $this->subEvent->withChildcareTimeRange(new TimeImmutableRange('15:00', null));
        $normalized = $this->normalizer->normalize($subEvent);

        $this->assertSame('15:00', $normalized['childcare']['start']);
        $this->assertNull($normalized['childcare']['end']);
    }

    /**
     * @test
     */
    public function it_includes_childcare_with_null_start_when_only_end_is_set(): void
    {
        $subEvent = $this->subEvent->withChildcareTimeRange(new TimeImmutableRange(null, '23:00'));
        $normalized = $this->normalizer->normalize($subEvent);

        $this->assertNull($normalized['childcare']['start']);
        $this->assertSame('23:00', $normalized['childcare']['end']);
    }
}
