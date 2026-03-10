<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class SubEventTest extends TestCase
{
    private SubEvent $subEvent;

    protected function setUp(): void
    {
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
    public function it_has_no_childcare_time_range_by_default(): void
    {
        $this->assertNull($this->subEvent->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_can_set_a_childcare_time_range(): void
    {
        $range = new TimeImmutableRange('15:00', '23:00');
        $updated = $this->subEvent->withChildcareTimeRange($range);

        $this->assertSame($range, $updated->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_can_clear_the_childcare_time_range(): void
    {
        $withRange = $this->subEvent->withChildcareTimeRange(new TimeImmutableRange('15:00', '23:00'));
        $cleared = $withRange->withChildcareTimeRange(null);

        $this->assertNull($cleared->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_returns_a_new_instance_when_setting_childcare_time_range(): void
    {
        $updated = $this->subEvent->withChildcareTimeRange(new TimeImmutableRange('15:00', '23:00'));

        $this->assertNotSame($this->subEvent, $updated);
        $this->assertNull($this->subEvent->getChildcareTimeRange());
    }
}
