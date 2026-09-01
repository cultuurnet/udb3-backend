<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

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
    public function it_has_no_overnight_stay_by_default(): void
    {
        $this->assertFalse($this->subEvent->hasOvernightStay());
    }

    /**
     * @test
     */
    public function it_can_be_set_to_have_an_overnight_stay(): void
    {
        $updated = $this->subEvent->withHasOvernightStay(true);

        $this->assertTrue($updated->hasOvernightStay());
    }

    /**
     * @test
     */
    public function it_can_clear_overnight_stay(): void
    {
        $withHasOvernightStay = $this->subEvent->withHasOvernightStay(true);
        $cleared = $withHasOvernightStay->withHasOvernightStay(false);

        $this->assertFalse($cleared->hasOvernightStay());
    }

    /**
     * @test
     */
    public function it_returns_a_new_instance_when_setting_overnight_stay(): void
    {
        $updated = $this->subEvent->withHasOvernightStay(true);

        $this->assertNotSame($this->subEvent, $updated);
        $this->assertFalse($this->subEvent->hasOvernightStay());
    }

    /**
     * @test
     */
    public function it_has_no_childcare_without_a_childcare_time_range(): void
    {
        $this->assertFalse($this->subEvent->hasChildcare());
    }

    /**
     * @test
     */
    public function it_has_childcare_with_a_childcare_time_range(): void
    {
        $subEvent = $this->subEvent->withChildcareTimeRange(
            new TimeImmutableRange(Time::fromString('08:00'), Time::fromString('18:00'))
        );

        $this->assertTrue($subEvent->hasChildcare());
    }

    /**
     * @test
     */
    public function it_has_no_childcare_with_an_empty_childcare_time_range(): void
    {
        $subEvent = $this->subEvent->withChildcareTimeRange(new TimeImmutableRange());

        $this->assertFalse($subEvent->hasChildcare());
    }
}
