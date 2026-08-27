<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use PHPUnit\Framework\TestCase;

class OpeningHoursTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_always_be_open_if_no_opening_hours_are_given(): void
    {
        $days = new Days(
            Day::monday(),
            Day::tuesday(),
            Day::wednesday()
        );

        $openingTime = new Time(
            new Hour(9),
            new Minute(0)
        );

        $closingTime = new Time(
            new Hour(12),
            new Minute(0)
        );

        $openingHour = new OpeningHour($days, $openingTime, $closingTime);

        $openingHours = new OpeningHours($openingHour);
        $openingHoursAlwaysOpen = new OpeningHours();

        $this->assertFalse($openingHours->isAlwaysOpen());
        $this->assertTrue($openingHoursAlwaysOpen->isAlwaysOpen());
    }

    /**
     * @test
     */
    public function it_removes_the_childcare_time_range_from_every_opening_hour(): void
    {
        $openingHours = new OpeningHours(
            (new OpeningHour(new Days(Day::monday()), Time::fromString('09:00'), Time::fromString('17:00')))
                ->withChildcareTimeRange(new TimeImmutableRange(Time::fromString('08:00'), Time::fromString('18:00'))),
            (new OpeningHour(new Days(Day::tuesday()), Time::fromString('10:00'), Time::fromString('16:00')))
                ->withChildcareTimeRange(new TimeImmutableRange(Time::fromString('09:00'), Time::fromString('17:00')))
        );

        $withoutChildcare = $openingHours->withoutChildcare();

        foreach ($withoutChildcare->toArray() as $openingHour) {
            $this->assertNull($openingHour->getChildcareTimeRange());
        }
    }

    /**
     * @test
     */
    public function it_keeps_the_opening_hours_intact_when_removing_the_childcare_time_range(): void
    {
        $openingHour = (new OpeningHour(new Days(Day::monday()), Time::fromString('09:00'), Time::fromString('17:00')))
            ->withChildcareTimeRange(new TimeImmutableRange(Time::fromString('08:00'), Time::fromString('18:00')));

        $withoutChildcare = (new OpeningHours($openingHour))->withoutChildcare();

        $this->assertEquals(
            new OpeningHours(new OpeningHour(new Days(Day::monday()), Time::fromString('09:00'), Time::fromString('17:00'))),
            $withoutChildcare
        );
        $this->assertNotNull($openingHour->getChildcareTimeRange());
    }

    /**
     * @test
     */
    public function it_has_childcare_when_any_opening_hour_has_childcare(): void
    {
        $openingHours = new OpeningHours(
            new OpeningHour(new Days(Day::monday()), Time::fromString('09:00'), Time::fromString('17:00')),
            (new OpeningHour(new Days(Day::tuesday()), Time::fromString('10:00'), Time::fromString('16:00')))
                ->withChildcareTimeRange(new TimeImmutableRange(Time::fromString('09:00'), Time::fromString('17:00')))
        );

        $this->assertTrue($openingHours->hasChildcare());
    }

    /**
     * @test
     */
    public function it_has_no_childcare_when_no_opening_hour_has_childcare(): void
    {
        $openingHours = new OpeningHours(
            new OpeningHour(new Days(Day::monday()), Time::fromString('09:00'), Time::fromString('17:00'))
        );

        $this->assertFalse($openingHours->hasChildcare());
        $this->assertFalse((new OpeningHours())->hasChildcare());
    }
}
