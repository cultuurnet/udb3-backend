<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
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
        $range = new TimeImmutableRange(Time::fromString('15:00'), Time::fromString('23:00'));
        $updated = $this->subEvent->withChildcareTimeRange($range);

        $this->assertSame($range, $updated->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_can_clear_the_childcare_time_range(): void
    {
        $withRange = $this->subEvent->withChildcareTimeRange(new TimeImmutableRange(Time::fromString('15:00'), Time::fromString('23:00')));
        $cleared = $withRange->withChildcareTimeRange(null);

        $this->assertNull($cleared->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_returns_a_new_instance_when_setting_childcare_time_range(): void
    {
        $updated = $this->subEvent->withChildcareTimeRange(new TimeImmutableRange(Time::fromString('15:00'), Time::fromString('23:00')));

        $this->assertNotSame($this->subEvent, $updated);
        $this->assertNull($this->subEvent->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_is_not_overnight_by_default(): void
    {
        $this->assertFalse($this->subEvent->isOvernight());
    }

    /**
     * @test
     */
    public function it_can_be_set_to_overnight(): void
    {
        $updated = $this->subEvent->withOvernight(true);

        $this->assertTrue($updated->isOvernight());
    }

    /**
     * @test
     */
    public function it_can_clear_overnight(): void
    {
        $withOvernight = $this->subEvent->withOvernight(true);
        $cleared = $withOvernight->withOvernight(false);

        $this->assertFalse($cleared->isOvernight());
    }

    /**
     * @test
     */
    public function it_returns_a_new_instance_when_setting_overnight(): void
    {
        $updated = $this->subEvent->withOvernight(true);

        $this->assertNotSame($this->subEvent, $updated);
        $this->assertFalse($this->subEvent->isOvernight());
    }
}
