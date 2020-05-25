<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarInterface;
use CultuurNet\UDB3\CalendarType;
use PHPUnit\Framework\TestCase;

class AvailableToTest extends TestCase
{
    /**
     * @test
     * @dataProvider calendarsDateProvider
     * @param CalendarInterface $calendar
     * @param \DateTimeInterface $expectedAvailableTo
     */
    public function it_creates_available_to_from_calendars(
        CalendarInterface $calendar,
        \DateTimeInterface $expectedAvailableTo
    ) {
        $availableTo = AvailableTo::createFromCalendar($calendar);

        $this->assertEquals(
            $expectedAvailableTo,
            $availableTo->getAvailableTo()
        );
    }

    /**
     * @return array
     */
    public function calendarsDateProvider()
    {
        $startDate = new \DateTime('2016-10-10T18:19:20');
        $endDate = new \DateTime('2016-10-18T20:19:18');
        $startDateNoHours = new \DateTime('2016-10-10');
        $endDateNoHours = new \DateTime('2016-10-18');
        $startDateAlmostMidnight = new \DateTime('2016-10-10T23:59:59');
        $endDateAlmostMidnight = new \DateTime('2016-10-18T23:59:59');

        return [
            [
                new Calendar(CalendarType::PERMANENT()),
                new \DateTime('2100-01-01T00:00:00Z'),
            ],
            [
                new Calendar(CalendarType::SINGLE(), $startDate),
                $startDate,
            ],
            [
                new Calendar(CalendarType::SINGLE(), $startDate, $endDate),
                $endDate,
            ],
            [
                new Calendar(CalendarType::PERIODIC(), $startDate, $endDate),
                $endDate,
            ],
            [
                new Calendar(CalendarType::MULTIPLE(), $startDate, $endDate),
                $endDate,
            ],
            [
                new Calendar(CalendarType::SINGLE(), $startDateNoHours),
                $startDateAlmostMidnight,
            ],
            [
                new Calendar(CalendarType::SINGLE(), $startDateNoHours, $endDateNoHours),
                $endDateAlmostMidnight,
            ],
            [
                new Calendar(CalendarType::PERIODIC(), $startDate, $endDateNoHours),
                $endDateAlmostMidnight,
            ],
            [
                new Calendar(CalendarType::MULTIPLE(), $startDate, $endDateNoHours),
                $endDateAlmostMidnight,
            ],
        ];
    }
}
