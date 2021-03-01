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

class PeriodicCalendarTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_a_calendar_type()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '18/12/2018');
        $openingHours = new OpeningHours();
        $calendar = new PeriodicCalendar(new DateRange($startDate, $endDate), $openingHours);

        $this->assertEquals(CalendarType::periodic(), $calendar->getType());
    }

    /**
     * @test
     */
    public function it_should_return_a_default_available_status()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '18/12/2018');
        $openingHours = new OpeningHours();
        $calendar = new PeriodicCalendar(new DateRange($startDate, $endDate), $openingHours);

        $this->assertEquals(new Status(StatusType::Available()), $calendar->getStatus());
    }

    /**
     * @test
     */
    public function it_allows_setting_an_explicit_status()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '18/12/2018');
        $openingHours = new OpeningHours();
        $calendar = new PeriodicCalendar(new DateRange($startDate, $endDate), $openingHours);
        $calendar = $calendar->withStatus(new Status(StatusType::Unavailable()));

        $this->assertEquals(new Status(StatusType::Unavailable()), $calendar->getStatus());
    }

    /**
     * @test
     */
    public function it_should_return_the_injected_start_and_end_date()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '18/12/2018');
        $openingHours = new OpeningHours();
        $calendar = new PeriodicCalendar(new DateRange($startDate, $endDate), $openingHours);

        $this->assertEquals($startDate, $calendar->getStartDate());
        $this->assertEquals($endDate, $calendar->getEndDate());
    }

    /**
     * @test
     */
    public function it_should_return_the_injected_opening_hours()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '18/12/2018');

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

        $calendar = new PeriodicCalendar(new DateRange($startDate, $endDate), $openingHours);

        $this->assertEquals($openingHours, $calendar->getOpeningHours());
    }
}
