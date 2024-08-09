<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
use PHPUnit\Framework\TestCase;

class MultipleSubEventsCalendarTest extends TestCase
{
    private SubEvents $subEvents;

    private MultipleSubEventsCalendar $multipleSubEventsCalendar;

    protected function setUp(): void
    {
        $this->subEvents = new SubEvents(
            new SubEvent(
                new DateRange(
                    DateTimeFactory::fromFormat('d/m/Y', '10/12/2018'),
                    DateTimeFactory::fromFormat('d/m/Y', '11/12/2018')
                ),
                new Status(StatusType::Available()),
                new BookingAvailability(BookingAvailabilityType::Available())
            ),
            new SubEvent(
                new DateRange(
                    DateTimeFactory::fromFormat('d/m/Y', '17/12/2018'),
                    DateTimeFactory::fromFormat('d/m/Y', '18/12/2018')
                ),
                new Status(StatusType::Available()),
                new BookingAvailability(BookingAvailabilityType::Available())
            )
        );

        $this->multipleSubEventsCalendar = new MultipleSubEventsCalendar($this->subEvents);
    }

    /**
     * @test
     */
    public function it_should_require_at_least_two_sub_events(): void
    {
        $startDate = DateTimeFactory::fromFormat('d/m/Y', '10/12/2018');
        $endDate = DateTimeFactory::fromFormat('d/m/Y', '11/12/2018');
        $dateRanges = new SubEvents(
            new SubEvent(
                new DateRange($startDate, $endDate),
                new Status(StatusType::Available()),
                new BookingAvailability(BookingAvailabilityType::Available())
            )
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Multiple date ranges calendar requires at least 2 date ranges.');

        new MultipleSubEventsCalendar($dateRanges);
    }

    /**
     * @test
     */
    public function it_should_return_a_start_and_end_date(): void
    {
        $this->assertEquals(
            DateTimeFactory::fromFormat('d/m/Y', '10/12/2018'),
            $this->multipleSubEventsCalendar->getStartDate()
        );
        $this->assertEquals(
            DateTimeFactory::fromFormat('d/m/Y', '18/12/2018'),
            $this->multipleSubEventsCalendar->getEndDate()
        );
    }

    /**
     * @test
     */
    public function it_should_return_multiple_sub_events(): void
    {
        $this->assertEquals($this->subEvents, $this->multipleSubEventsCalendar->getSubEvents());
    }

    /**
     * @test
     */
    public function it_should_return_a_calendar_type(): void
    {
        $this->assertEquals(CalendarType::multiple(), $this->multipleSubEventsCalendar->getType());
    }

    /**
     * @test
     */
    public function it_should_return_a_default_available_status(): void
    {
        $this->assertEquals(new Status(StatusType::Available()), $this->multipleSubEventsCalendar->getStatus());
    }

    /**
     * @test
     */
    public function it_has_a_default_booking_availability(): void
    {
        $this->assertEquals(
            new BookingAvailability(BookingAvailabilityType::Available()),
            $this->multipleSubEventsCalendar->getBookingAvailability()
        );
    }

    /**
     * @test
     */
    public function it_allows_setting_an_explicit_status(): void
    {
        $calendar = $this->multipleSubEventsCalendar->withStatus(new Status(StatusType::Available()));

        $this->assertEquals(new Status(StatusType::Available()), $calendar->getStatus());
    }

    /**
     * @test
     */
    public function it_allows_setting_an_explicit_booking_availability(): void
    {
        $calendar = $this->multipleSubEventsCalendar->withBookingAvailability(
            new BookingAvailability(BookingAvailabilityType::Unavailable())
        );

        $this->assertEquals(
            new BookingAvailability(BookingAvailabilityType::Unavailable()),
            $calendar->getBookingAvailability()
        );
    }
}
