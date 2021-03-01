<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use PHPUnit\Framework\TestCase;

class MultipleSubEventsCalendarTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_require_at_least_two_sub_events()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '11/12/2018');
        $dateRanges = new SubEvents(
            new SubEvent(new DateRange($startDate, $endDate), new Status(StatusType::Available()))
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Multiple date ranges calendar requires at least 2 date ranges.');

        new MultipleSubEventsCalendar($dateRanges);
    }

    /**
     * @test
     */
    public function it_should_return_a_start_and_end_date()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '18/12/2018');

        $dateRanges = new SubEvents(
            new SubEvent(
                new DateRange(
                    $startDate,
                    \DateTimeImmutable::createFromFormat('d/m/Y', '11/12/2018')
                ),
                new Status(StatusType::Available())
            ),
            new SubEvent(
                new DateRange(
                    \DateTimeImmutable::createFromFormat('d/m/Y', '17/12/2018'),
                    $endDate
                ),
                new Status(StatusType::Available())
            )
        );

        $calendar = new MultipleSubEventsCalendar($dateRanges);

        $this->assertEquals($startDate, $calendar->getStartDate());
        $this->assertEquals($endDate, $calendar->getEndDate());
    }

    /**
     * @test
     */
    public function it_should_return_multiple_sub_events()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '18/12/2018');

        $dateRanges = new SubEvents(
            new SubEvent(
                new DateRange(
                    $startDate,
                    \DateTimeImmutable::createFromFormat('d/m/Y', '11/12/2018')
                ),
                new Status(StatusType::Available())
            ),
            new SubEvent(
                new DateRange(
                    \DateTimeImmutable::createFromFormat('d/m/Y', '17/12/2018'),
                    $endDate
                ),
                new Status(StatusType::Available())
            )
        );

        $calendar = new MultipleSubEventsCalendar($dateRanges);

        $this->assertEquals($dateRanges, $calendar->getSubEvents());
    }

    /**
     * @test
     */
    public function it_should_return_a_calendar_type()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '18/12/2018');

        $dateRanges = new SubEvents(
            new SubEvent(
                new DateRange(
                    $startDate,
                    \DateTimeImmutable::createFromFormat('d/m/Y', '11/12/2018')
                ),
                new Status(StatusType::Available())
            ),
            new SubEvent(
                new DateRange(
                    \DateTimeImmutable::createFromFormat('d/m/Y', '17/12/2018'),
                    $endDate
                ),
                new Status(StatusType::Available())
            )
        );

        $calendar = new MultipleSubEventsCalendar($dateRanges);

        $this->assertEquals(CalendarType::multiple(), $calendar->getType());
    }

    /**
     * @test
     */
    public function it_should_return_a_default_available_status()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '18/12/2018');

        $dateRanges = new SubEvents(
            new SubEvent(
                new DateRange(
                    $startDate,
                    \DateTimeImmutable::createFromFormat('d/m/Y', '11/12/2018')
                ),
                new Status(StatusType::Unavailable())
            ),
            new SubEvent(
                new DateRange(
                    \DateTimeImmutable::createFromFormat('d/m/Y', '17/12/2018'),
                    $endDate
                ),
                new Status(StatusType::TemporarilyUnavailable())
            )
        );

        $calendar = new MultipleSubEventsCalendar($dateRanges);

        $this->assertEquals(new Status(StatusType::Available()), $calendar->getStatus());
    }

    /**
     * @test
     */
    public function it_allows_setting_an_explicit_status()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '18/12/2018');

        $dateRanges = new SubEvents(
            new SubEvent(
                new DateRange(
                    $startDate,
                    \DateTimeImmutable::createFromFormat('d/m/Y', '11/12/2018')
                ),
                new Status(StatusType::Unavailable())
            ),
            new SubEvent(
                new DateRange(
                    \DateTimeImmutable::createFromFormat('d/m/Y', '17/12/2018'),
                    $endDate
                ),
                new Status(StatusType::TemporarilyUnavailable())
            )
        );

        $calendar = new MultipleSubEventsCalendar($dateRanges);

        $calendar = $calendar->withStatus(new Status(StatusType::Available()));

        $this->assertEquals(new Status(StatusType::Available()), $calendar->getStatus());
    }
}
