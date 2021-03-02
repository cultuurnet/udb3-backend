<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use PHPUnit\Framework\TestCase;

class SingleSubEventCalendarTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_a_calendar_type()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '18/12/2018');
        $calendar = new SingleSubEventCalendar(
            new SubEvent(
                new DateRange($startDate, $endDate),
                new Status(StatusType::Available())
            )
        );

        $this->assertEquals(CalendarType::single(), $calendar->getType());
    }

    /**
     * @test
     */
    public function it_should_return_a_default_available_status()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '18/12/2018');
        $calendar = new SingleSubEventCalendar(
            new SubEvent(
                new DateRange($startDate, $endDate),
                new Status(StatusType::Unavailable())
            )
        );

        $this->assertEquals(new Status(StatusType::Available()), $calendar->getStatus());
    }

    /**
     * @test
     */
    public function it_allows_setting_an_explicit_status()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '18/12/2018');
        $calendar = new SingleSubEventCalendar(
            new SubEvent(
                new DateRange($startDate, $endDate),
                new Status(StatusType::Unavailable())
            )
        );
        $calendar = $calendar->withStatus(new Status(StatusType::Available()));

        $this->assertEquals(new Status(StatusType::Available()), $calendar->getStatus());
    }

    /**
     * @test
     */
    public function it_should_return_the_injected_start_and_end_date()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '18/12/2018');
        $calendar = new SingleSubEventCalendar(
            new SubEvent(
                new DateRange($startDate, $endDate),
                new Status(StatusType::Available())
            )
        );

        $this->assertEquals($startDate, $calendar->getStartDate());
        $this->assertEquals($endDate, $calendar->getEndDate());
    }

    /**
     * @test
     */
    public function it_should_return_a_single_sub_event()
    {
        $startDate = \DateTimeImmutable::createFromFormat('d/m/Y', '10/12/2018');
        $endDate = \DateTimeImmutable::createFromFormat('d/m/Y', '18/12/2018');
        $calendar = new SingleSubEventCalendar(
            new SubEvent(
                new DateRange($startDate, $endDate),
                new Status(StatusType::Available())
            )
        );

        $expected = new SubEvents(
            new SubEvent(
                new DateRange($startDate, $endDate),
                new Status(StatusType::Available())
            )
        );

        $this->assertEquals($expected, $calendar->getSubEvents());
    }
}
