<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Contact\BookingInfo;
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
                new BookingInfo(),
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
    public function it_skips_sub_event_with_remaining_capacity_when_setting_booking_availability_on_sub_events(): void
    {
        $subEventWithRemainingCapacity = new SubEvent(
            new DateRange(
                DateTimeFactory::fromFormat('d/m/Y', '10/12/2018'),
                DateTimeFactory::fromFormat('d/m/Y', '18/12/2018')
            ),
            new Status(StatusType::Available()),
            (new BookingAvailability(BookingAvailabilityType::Available()))->withRemainingCapacity(42),
            new BookingInfo(),
        );

        $calendar = (new SingleSubEventCalendar($subEventWithRemainingCapacity))
            ->withBookingAvailabilityOnSubEvents(new BookingAvailability(BookingAvailabilityType::Unavailable()));

        $this->assertEquals(
            (new BookingAvailability(BookingAvailabilityType::Available()))->withRemainingCapacity(42),
            $calendar->getSubEvents()->getFirst()->getBookingAvailability()
        );
    }

    /**
     * @test
     */
    public function it_preserves_sub_event_capacity_when_updating_type_via_booking_availability_on_sub_events(): void
    {
        $subEventWithCapacity = new SubEvent(
            new DateRange(
                DateTimeFactory::fromFormat('d/m/Y', '10/12/2018'),
                DateTimeFactory::fromFormat('d/m/Y', '18/12/2018')
            ),
            new Status(StatusType::Available()),
            (new BookingAvailability(BookingAvailabilityType::Available()))->withCapacity(100),
            new BookingInfo(),
        );

        $calendar = (new SingleSubEventCalendar($subEventWithCapacity))
            ->withBookingAvailabilityOnSubEvents(new BookingAvailability(BookingAvailabilityType::Unavailable()));

        $this->assertEquals(
            (new BookingAvailability(BookingAvailabilityType::Unavailable()))->withCapacity(100),
            $calendar->getSubEvents()->getFirst()->getBookingAvailability()
        );
    }

    /**
     * @test
     */
    public function it_skips_sub_event_with_both_capacity_and_remaining_capacity_when_setting_booking_availability_on_sub_events(): void
    {
        $subEventWithBoth = new SubEvent(
            new DateRange(
                DateTimeFactory::fromFormat('d/m/Y', '10/12/2018'),
                DateTimeFactory::fromFormat('d/m/Y', '18/12/2018')
            ),
            new Status(StatusType::Available()),
            (new BookingAvailability(BookingAvailabilityType::Available()))->withCapacity(100)->withRemainingCapacity(42),
            new BookingInfo(),
        );

        $calendar = (new SingleSubEventCalendar($subEventWithBoth))
            ->withBookingAvailabilityOnSubEvents(new BookingAvailability(BookingAvailabilityType::Unavailable()));

        $this->assertEquals(
            (new BookingAvailability(BookingAvailabilityType::Available()))->withCapacity(100)->withRemainingCapacity(42),
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
                new BookingInfo(),
            )
        );

        $this->assertEquals($expected, $this->singleSubEventCalendar->getSubEvents());
    }
}
