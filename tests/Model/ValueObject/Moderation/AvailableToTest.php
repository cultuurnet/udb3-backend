<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Moderation;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\EventTypeResolver;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Calendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\MultipleSubEventsCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PeriodicCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvents;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\TestCase;

class AvailableToTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_an_immutable_datetime_set_in_2100(): void
    {
        $expected = '2100-01-01T00:00:00+00:00';
        $actual = AvailableTo::forever()->format(DateTimeInterface::ATOM);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_be_creatable_from_a_calendar(): void
    {
        $startDate = DateTimeFactory::fromFormat('d/m/Y', '10/01/2018');
        $endDate = DateTimeFactory::fromFormat('d/m/Y', '11/01/2018');

        $singleDateRangeCalendar = new SingleSubEventCalendar(
            new SubEvent(
                new DateRange($startDate, $endDate),
                new Status(StatusType::Available()),
                new BookingAvailability(BookingAvailabilityType::Available())
            )
        );

        $permanentCalendar = new PermanentCalendar(new OpeningHours());

        $availableToFromSingleDateRange = AvailableTo::createFromCalendar($singleDateRangeCalendar, null);
        $availableToFromPermanent = AvailableTo::createFromCalendar($permanentCalendar, null);

        $this->assertEquals($endDate, $availableToFromSingleDateRange);
        $this->assertEquals(AvailableTo::forever(), $availableToFromPermanent);
    }

    /**
     * @test
     * @dataProvider calendarsDateProvider
     */
    public function it_creates_available_to_from_calendars(
        Calendar $calendar,
        \DateTimeInterface $expectedAvailableTo
    ): void {
        $availableTo = AvailableTo::createFromCalendar($calendar, null);

        $this->assertEquals(
            $expectedAvailableTo,
            $availableTo
        );
    }

    public function calendarsDateProvider(): array
    {
        $startDate = new \DateTimeImmutable('2016-10-10T18:19:20');
        $endDate = new \DateTimeImmutable('2016-10-18T20:19:18');
        $startDateNoHours = new \DateTimeImmutable('2016-10-10');
        $endDateNoHours = new \DateTimeImmutable('2016-10-18');
        $startDateAlmostMidnight = new \DateTimeImmutable('2016-10-10T23:59:59');
        $endDateAlmostMidnight = new \DateTimeImmutable('2016-10-18T23:59:59');

        return [
            'permanent calendar' => [
                new PermanentCalendar(new OpeningHours()),
                new \DateTimeImmutable('2100-01-01T00:00:00Z'),
            ],
            'single calendar with end date equal to start date' => [
                new SingleSubEventCalendar(
                    SubEvent::createAvailable(new DateRange($startDate, $startDate))
                ),
                $startDate,
            ],
            'single calendar' => [
                new SingleSubEventCalendar(
                    SubEvent::createAvailable(new DateRange($startDate, $endDate))
                ),
                $endDate,
            ],
            'periodic calendar' => [
                new PeriodicCalendar(
                    new DateRange($startDate, $endDate),
                    new OpeningHours()
                ),
                $endDate,
            ],
            'multiple calendar' => [
                new MultipleSubEventsCalendar(
                    new SubEvents(
                        SubEvent::createAvailable(
                            new DateRange(
                                new \DateTimeImmutable('2016-08-08T18:19:20'),
                                new \DateTimeImmutable('2016-08-08T20:21:22')
                            )
                        ),
                        SubEvent::createAvailable(new DateRange($startDate, $endDate))
                    )
                ),
                $endDate,
            ],
            'single calendar no hours and end date equal to start date' => [
                new SingleSubEventCalendar(
                    SubEvent::createAvailable(new DateRange($startDateNoHours, $startDateNoHours))
                ),
                $startDateAlmostMidnight,
            ],
            'periodic calendar no hours and end date equal to start date' => [
                new PeriodicCalendar(
                    new DateRange($startDateNoHours, $startDateNoHours),
                    new OpeningHours()
                ),
                $startDateAlmostMidnight,
            ],
            'periodic calendar no hours' => [
                new PeriodicCalendar(
                    new DateRange($startDateNoHours, $endDateNoHours),
                    new OpeningHours()
                ),
                $endDateAlmostMidnight,
            ],
            'multiple calendar and no hours' => [
                new MultipleSubEventsCalendar(
                    new SubEvents(
                        SubEvent::createAvailable(
                            new DateRange(
                                new \DateTimeImmutable('2016-08-08T18:19:20'),
                                new \DateTimeImmutable('2016-08-08T20:21:22')
                            )
                        ),
                        SubEvent::createAvailable(new DateRange($startDate, $endDateNoHours))
                    )
                ),
                $endDateAlmostMidnight,
            ],
        ];
    }

    /**
     * @test
     */
    public function it_will_use_start_date_for_certain_event_types(): void
    {
        $startDate = new DateTimeImmutable('2016-10-10T18:19:20');
        $endDate = new DateTimeImmutable('2020-10-10T18:19:20');
        $calendar = new MultipleSubEventsCalendar(
            new SubEvents(
                SubEvent::createAvailable(new DateRange($startDate, $endDate)),
                SubEvent::createAvailable(new DateRange($startDate, $endDate))
            )
        );
        $eventTypeResolver = new EventTypeResolver();

        $availableTo = AvailableTo::createFromCalendar($calendar, $eventTypeResolver->byId('0.7.0.0.0'));
        $this->assertEquals($endDate, $availableTo);

        $availableTo = AvailableTo::createFromCalendar($calendar, $eventTypeResolver->byId('0.3.1.0.0'));
        $this->assertEquals($startDate, $availableTo);
    }
}
