<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
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
    private PeriodicCalendar $periodicCalendar;

    protected function setUp(): void
    {
        $this->periodicCalendar = new PeriodicCalendar(
            new DateRange(
                DateTimeFactory::fromFormat('d/m/Y', '10/12/2018'),
                DateTimeFactory::fromFormat('d/m/Y', '18/12/2018')
            ),
            new OpeningHours()
        );
    }

    /**
     * @test
     */
    public function it_should_return_a_calendar_type(): void
    {
        $this->assertEquals(CalendarType::periodic(), $this->periodicCalendar->getType());
    }

    /**
     * @test
     */
    public function it_should_return_a_default_available_status(): void
    {
        $this->assertEquals(new Status(StatusType::Available()), $this->periodicCalendar->getStatus());
    }

    /**
     * @test
     */
    public function it_has_a_default_booking_availability(): void
    {
        $this->assertEquals(
            new BookingAvailability(BookingAvailabilityType::Available()),
            $this->periodicCalendar->getBookingAvailability()
        );
    }

    /**
     * @test
     */
    public function it_allows_setting_an_explicit_status(): void
    {
        $calendar = $this->periodicCalendar->withStatus(new Status(StatusType::Unavailable()));

        $this->assertEquals(new Status(StatusType::Unavailable()), $calendar->getStatus());
    }

    /**
     * @test
     */
    public function it_allows_setting_an_explicit_booking_availability(): void
    {
        $calendar = $this->periodicCalendar->withBookingAvailability(
            new BookingAvailability(BookingAvailabilityType::Unavailable())
        );

        $this->assertEquals(
            new BookingAvailability(BookingAvailabilityType::Unavailable()),
            $calendar->getBookingAvailability()
        );
    }

    /**
     * @test
     */
    public function it_should_return_the_injected_start_and_end_date(): void
    {
        $this->assertEquals(
            DateTimeFactory::fromFormat('d/m/Y', '10/12/2018'),
            $this->periodicCalendar->getStartDate()
        );
        $this->assertEquals(
            DateTimeFactory::fromFormat('d/m/Y', '18/12/2018'),
            $this->periodicCalendar->getEndDate()
        );
    }

    /**
     * @test
     */
    public function it_should_return_the_injected_opening_hours(): void
    {
        $startDate = DateTimeFactory::fromFormat('d/m/Y', '10/12/2018');
        $endDate = DateTimeFactory::fromFormat('d/m/Y', '18/12/2018');

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
                new Time(
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
                new Time(
                    new Hour(17),
                    new Minute(0)
                )
            )
        );

        $calendar = new PeriodicCalendar(new DateRange($startDate, $endDate), $openingHours);

        $this->assertEquals($openingHours, $calendar->getOpeningHours());
    }
}
