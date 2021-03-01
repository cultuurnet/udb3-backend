<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\EventTypeResolver;
use CultuurNet\UDB3\Timestamp;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class AvailableToTest extends TestCase
{
    /**
     * @test
     * @dataProvider calendarsDateProvider
     */
    public function it_creates_available_to_from_calendars(
        Calendar $calendar,
        \DateTimeInterface $expectedAvailableTo
    ) {
        $availableTo = AvailableTo::createFromCalendar($calendar);

        $this->assertEquals(
            $expectedAvailableTo,
            $availableTo->getAvailableTo()
        );
    }

    /**
     * @test
     */
    public function it_will_use_start_date_for_certain_event_types(): void
    {
        $startDate = new \DateTime('2016-10-10T18:19:20');
        $endDate = new \DateTime('2020-10-10T18:19:20');
        $calendar = new Calendar(CalendarType::MULTIPLE(), null, null, [new Timestamp($startDate, $endDate)]);
        $eventTypeResolver = new EventTypeResolver();

        $availableTo = AvailableTo::createFromCalendar($calendar, $eventTypeResolver->byId(new StringLiteral('0.7.0.0.0')));
        $this->assertEquals($endDate, $availableTo->getAvailableTo());

        $availableTo = AvailableTo::createFromCalendar($calendar, $eventTypeResolver->byId(new StringLiteral('0.3.1.0.0')));
        $this->assertEquals($startDate, $availableTo->getAvailableTo());
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
                new Calendar(CalendarType::SINGLE(), null, null, [new Timestamp($startDate, $startDate)]),
                $startDate,
            ],
            [
                new Calendar(CalendarType::SINGLE(), null, null, [new Timestamp($startDate, $endDate)]),
                $endDate,
            ],
            [
                new Calendar(CalendarType::PERIODIC(), $startDate, $endDate),
                $endDate,
            ],
            [
                new Calendar(CalendarType::MULTIPLE(), $startDate, $endDate, [new Timestamp($startDate, $endDate)]),
                $endDate,
            ],
            [
                new Calendar(CalendarType::SINGLE(), null, null, [new Timestamp($startDateNoHours, $startDateNoHours)]),
                $startDateAlmostMidnight,
            ],
            [
                new Calendar(CalendarType::SINGLE(), null, null, [new Timestamp($startDateNoHours, $endDateNoHours)]),
                $endDateAlmostMidnight,
            ],
            [
                new Calendar(CalendarType::PERIODIC(), $startDate, $endDateNoHours),
                $endDateAlmostMidnight,
            ],
            [
                new Calendar(CalendarType::MULTIPLE(), null, null, [new Timestamp($startDate, $endDateNoHours)]),
                $endDateAlmostMidnight,
            ],
        ];
    }
}
