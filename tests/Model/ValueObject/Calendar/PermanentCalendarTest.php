<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use PHPUnit\Framework\TestCase;

class PermanentCalendarTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_a_calendar_type()
    {
        $calendar = new PermanentCalendar(new OpeningHours());
        $this->assertEquals(CalendarType::permanent(), $calendar->getType());
    }

    /**
     * @test
     */
    public function it_should_return_a_default_available_status()
    {
        $calendar = new PermanentCalendar(new OpeningHours());

        $this->assertEquals(new Status(StatusType::Available()), $calendar->getStatus());
    }

    /**
     * @test
     */
    public function it_allows_setting_an_explicit_status()
    {
        $calendar = new PermanentCalendar(new OpeningHours());
        $calendar = $calendar->withStatus(new Status(StatusType::Unavailable()));

        $this->assertEquals(new Status(StatusType::Unavailable()), $calendar->getStatus());
    }

    /**
     * @test
     */
    public function it_should_return_the_injected_opening_hours()
    {
        $days = new Days(
            Day::monday(),
            Day::tuesday(),
            Day::wednesday()
        );

        $openingHours = new OpeningHours(
            new OpeningHour(
                $days,
                new Time(
                    new Hour(9),
                    new Minute(0)
                ),
                $closingTime = new Time(
                    new Hour(12),
                    new Minute(0)
                )
            ),
            new OpeningHour(
                $days,
                new Time(
                    new Hour(13),
                    new Minute(0)
                ),
                $closingTime = new Time(
                    new Hour(17),
                    new Minute(0)
                )
            )
        );

        $calendar = new PermanentCalendar($openingHours);

        $this->assertEquals($openingHours, $calendar->getOpeningHours());
    }
}
