<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Moderation;

use CultuurNet\UDB3\Calendar\Calendar as LegacyCalendar;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\EventTypeResolver;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SingleSubEventCalendar;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
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

        $availableToFromSingleDateRange = AvailableTo::createFromCalendar($singleDateRangeCalendar);
        $availableToFromPermanent = AvailableTo::createFromCalendar($permanentCalendar);

        $this->assertEquals($endDate, $availableToFromSingleDateRange);
        $this->assertEquals(AvailableTo::forever(), $availableToFromPermanent);
    }

    /**
     * @test
     * @dataProvider calendarsDateProvider
     */
    public function it_creates_available_to_from_calendars(
        LegacyCalendar $calendar,
        \DateTimeInterface $expectedAvailableTo
    ): void {
        $availableTo = AvailableTo::createFromLegacyCalendar($calendar);

        $this->assertEquals(
            $expectedAvailableTo,
            $availableTo
        );
    }

    public function calendarsDateProvider(): array
    {
        $startDate = new \DateTime('2016-10-10T18:19:20');
        $endDate = new \DateTime('2016-10-18T20:19:18');
        $startDateNoHours = new \DateTime('2016-10-10');
        $endDateNoHours = new \DateTime('2016-10-18');
        $startDateAlmostMidnight = new \DateTime('2016-10-10T23:59:59');
        $endDateAlmostMidnight = new \DateTime('2016-10-18T23:59:59');

        return [
            [
                new LegacyCalendar(CalendarType::permanent()),
                new \DateTime('2100-01-01T00:00:00Z'),
            ],
            [
                new LegacyCalendar(
                    CalendarType::single(),
                    null,
                    null,
                    [
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeImmutable::createFromMutable($startDate),
                                DateTimeImmutable::createFromMutable($startDate)
                            )
                        ),
                    ]
                ),
                $startDate,
            ],
            [
                new LegacyCalendar(
                    CalendarType::single(),
                    null,
                    null,
                    [
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeImmutable::createFromMutable($startDate),
                                DateTimeImmutable::createFromMutable($endDate)
                            )
                        ),
                    ]
                ),
                $endDate,
            ],
            [
                new LegacyCalendar(CalendarType::periodic(), $startDate, $endDate),
                $endDate,
            ],
            [
                new LegacyCalendar(
                    CalendarType::multiple(),
                    null,
                    null,
                    [
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeImmutable::createFromMutable($startDate),
                                DateTimeImmutable::createFromMutable($endDate)
                            )
                        ),
                    ]
                ),
                $endDate,
            ],
            [
                new LegacyCalendar(
                    CalendarType::single(),
                    null,
                    null,
                    [
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeImmutable::createFromMutable($startDateNoHours),
                                DateTimeImmutable::createFromMutable($startDateNoHours)
                            )
                        ),
                    ]
                ),
                $startDateAlmostMidnight,
            ],
            [
                new LegacyCalendar(
                    CalendarType::single(),
                    null,
                    null,
                    [
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeImmutable::createFromMutable($startDateNoHours),
                                DateTimeImmutable::createFromMutable($endDateNoHours)
                            )
                        ),
                    ]
                ),
                $endDateAlmostMidnight,
            ],
            [
                new LegacyCalendar(CalendarType::periodic(), $startDate, $endDateNoHours),
                $endDateAlmostMidnight,
            ],
            [
                new LegacyCalendar(
                    CalendarType::multiple(),
                    null,
                    null,
                    [
                        SubEvent::createAvailable(
                            new DateRange(
                                DateTimeImmutable::createFromMutable($startDate),
                                DateTimeImmutable::createFromMutable($endDateNoHours)
                            )
                        ),
                    ]
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
        $calendar = new LegacyCalendar(
            CalendarType::multiple(),
            null,
            null,
            [
                SubEvent::createAvailable(new DateRange($startDate, $endDate)),
            ]
        );
        $eventTypeResolver = new EventTypeResolver();

        $availableTo = AvailableTo::createFromLegacyCalendar($calendar, EventType::fromUdb3ModelCategory($eventTypeResolver->byId('0.7.0.0.0')));
        $this->assertEquals($endDate, $availableTo);

        $availableTo = AvailableTo::createFromLegacyCalendar($calendar, EventType::fromUdb3ModelCategory($eventTypeResolver->byId('0.3.1.0.0')));
        $this->assertEquals($startDate, $availableTo);
    }
}
