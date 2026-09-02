<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\TimeImmutableRange;
use PHPUnit\Framework\TestCase;

class SingleSubEventCalendarTest extends TestCase
{
    private SingleSubEventCalendar $singleSubEventCalendar;

    protected function setUp(): void
    {
        $this->singleSubEventCalendar = new SingleSubEventCalendar(
            new SubEvent(
                new DateRange(
                    DateTimeFactory::fromFormat('d/m/Y', '10/12/2018'),
                    DateTimeFactory::fromFormat('d/m/Y', '18/12/2018')
                ),
                new Status(StatusType::Available()),
                new BookingAvailability(BookingAvailabilityType::Available()),
            )
        );
    }

    /**
     * @test
     */
    public function it_should_return_a_calendar_type(): void
    {
        $this->assertEquals(CalendarType::single(), $this->singleSubEventCalendar->getType());
    }

    /**
     * @test
     */
    public function it_should_return_a_default_available_status(): void
    {
        $this->assertEquals(new Status(StatusType::Available()), $this->singleSubEventCalendar->getStatus());
    }

    /**
     * @test
     */
    public function it_has_a_default_booking_availability(): void
    {
        $this->assertEquals(
            new BookingAvailability(BookingAvailabilityType::Available()),
            $this->singleSubEventCalendar->getBookingAvailability()
        );
    }

    /**
     * @test
     */
    public function it_allows_setting_an_explicit_status(): void
    {
        $calendar = $this->singleSubEventCalendar->withStatus(new Status(StatusType::Unavailable()));

        $this->assertEquals(new Status(StatusType::Unavailable()), $calendar->getStatus());
    }

    /**
     * @test
     */
    public function it_allows_setting_an_explicit_status_on_sub_events(): void
    {
        $calendar = $this->singleSubEventCalendar->withStatusOnSubEvents(new Status(StatusType::Unavailable()));

        $this->assertEquals(new Status(StatusType::Unavailable()), $calendar->getSubEvents()->getFirst()->getStatus());
    }

    /**
     * @test
     */
    public function it_allows_setting_an_explicit_booking_availability(): void
    {
        $calendar = $this->singleSubEventCalendar->withBookingAvailability(
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
    public function it_allows_setting_an_explicit_booking_availability_on_sub_events(): void
    {
        $calendar = $this->singleSubEventCalendar->withBookingAvailabilityOnSubEvents(
            new BookingAvailability(BookingAvailabilityType::Unavailable())
        );

        $this->assertEquals(
            new BookingAvailability(BookingAvailabilityType::Unavailable()),
            $calendar->getSubEvents()->getFirst()->getBookingAvailability()
        );
    }

    /**
     * @test
     */
    public function it_should_return_the_injected_start_and_end_date(): void
    {
        $this->assertEquals(
            DateTimeFactory::fromFormat('d/m/Y', '10/12/2018'),
            $this->singleSubEventCalendar->getStartDate()
        );
        $this->assertEquals(
            DateTimeFactory::fromFormat('d/m/Y', '18/12/2018'),
            $this->singleSubEventCalendar->getEndDate()
        );
    }

    /**
     * @test
     */
    public function it_should_return_a_single_sub_event(): void
    {
        $expected = new SubEvents(
            new SubEvent(
                new DateRange(
                    DateTimeFactory::fromFormat('d/m/Y', '10/12/2018'),
                    DateTimeFactory::fromFormat('d/m/Y', '18/12/2018')
                ),
                new Status(StatusType::Available()),
                new BookingAvailability(BookingAvailabilityType::Available()),
            )
        );

        $this->assertEquals($expected, $this->singleSubEventCalendar->getSubEvents());
    }

    /**
     * @test
     */
    public function it_has_no_childcare_by_default(): void
    {
        $this->assertFalse($this->singleSubEventCalendar->hasChildcare());
    }

    /**
     * @test
     */
    public function it_reports_childcare_on_its_sub_event(): void
    {
        $calendar = new SingleSubEventCalendar(
            SubEvent::createAvailable(
                new DateRange(
                    DateTimeFactory::fromFormat('d/m/Y', '10/12/2018'),
                    DateTimeFactory::fromFormat('d/m/Y', '18/12/2018')
                )
            )->withChildcareTimeRange(
                new TimeImmutableRange(Time::fromString('08:00'), Time::fromString('18:00'))
            )
        );

        $this->assertTrue($calendar->hasChildcare());
    }

    /**
     * @test
     */
    public function it_removes_the_childcare_from_its_sub_event(): void
    {
        $withChildcare = new SingleSubEventCalendar(
            SubEvent::createAvailable(
                new DateRange(
                    DateTimeFactory::fromFormat('d/m/Y', '10/12/2018'),
                    DateTimeFactory::fromFormat('d/m/Y', '18/12/2018')
                )
            )->withChildcareTimeRange(
                new TimeImmutableRange(Time::fromString('08:00'), Time::fromString('18:00'))
            )
        );

        $withoutChildcare = $withChildcare->withoutChildcare();

        $this->assertFalse($withoutChildcare->hasChildcare());
        $this->assertTrue($withChildcare->hasChildcare());
    }

    /**
     * @test
     */
    public function it_keeps_the_status_and_the_booking_availability_when_removing_the_childcare(): void
    {
        $calendar = $this->singleSubEventCalendar
            ->withStatus(new Status(StatusType::Unavailable()))
            ->withBookingAvailability(new BookingAvailability(BookingAvailabilityType::Unavailable()));

        $this->assertEquals($calendar, $calendar->withoutChildcare());
    }

    /**
     * @test
     */
    public function it_removes_the_overnight_stay_from_its_sub_event(): void
    {
        $withOvernightStay = new SingleSubEventCalendar(
            SubEvent::createAvailable(
                new DateRange(
                    DateTimeFactory::fromFormat('d/m/Y', '10/12/2018'),
                    DateTimeFactory::fromFormat('d/m/Y', '18/12/2018')
                )
            )->withHasOvernightStay(true)
        );

        $subEvents = $withOvernightStay->withoutOvernightStay()->getSubEvents()->toArray();

        $this->assertFalse($subEvents[0]->hasOvernightStay());
        $this->assertTrue($withOvernightStay->getSubEvents()->toArray()[0]->hasOvernightStay());
    }
}
