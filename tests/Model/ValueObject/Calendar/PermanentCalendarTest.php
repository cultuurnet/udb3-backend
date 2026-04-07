<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class PermanentCalendarTest extends TestCase
{
    /**
     * @test
     */
    public function it_should_return_a_calendar_type(): void
    {
        $calendar = new PermanentCalendar(new OpeningHours());
        $this->assertEquals(CalendarType::permanent(), $calendar->getType());
    }

    /**
     * @test
     */
    public function it_should_return_a_default_available_status(): void
    {
        $calendar = new PermanentCalendar(new OpeningHours());

        $this->assertEquals(new Status(StatusType::Available()), $calendar->getStatus());
    }

    /**
     * @test
     */
    public function it_has_a_default_booking_availability(): void
    {
        $calendar = new PermanentCalendar(new OpeningHours());

        $this->assertEquals(
            new BookingAvailability(BookingAvailabilityType::Available()),
            $calendar->getBookingAvailability()
        );
    }

    /**
     * @test
     */
    public function it_allows_setting_an_explicit_status(): void
    {
        $calendar = new PermanentCalendar(new OpeningHours());
        $calendar = $calendar->withStatus(new Status(StatusType::Unavailable()));

        $this->assertEquals(new Status(StatusType::Unavailable()), $calendar->getStatus());
    }

    /**
     * @test
     */
    public function it_allows_setting_an_explicit_booking_availability(): void
    {
        $calendar = new PermanentCalendar(new OpeningHours());
        $calendar = $calendar->withBookingAvailability(
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
    public function it_should_return_the_injected_opening_hours(): void
    {
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

        $calendar = new PermanentCalendar($openingHours);

        $this->assertEquals($openingHours, $calendar->getOpeningHours());
    }

    /**
     * @test
     */
    public function it_has_empty_adjusted_opening_hours_by_default(): void
    {
        $calendar = new PermanentCalendar(new OpeningHours());

        $this->assertTrue($calendar->getAdjustedOpeningHours()->isEmpty());
        $this->assertEquals(0, $calendar->getAdjustedOpeningHours()->count());
    }

    /**
     * @test
     */
    public function it_allows_setting_adjusted_opening_hours(): void
    {
        $calendar = new PermanentCalendar(new OpeningHours());

        $openingHours = new OpeningHours(
            new OpeningHour(new Days(Day::monday()), Time::fromString('09:00'), Time::fromString('17:00'))
        );
        $entry = new AdjustedOpeningHours(
            new DateTimeImmutable('2026-12-25'),
            new DateTimeImmutable('2026-12-26'),
            $openingHours
        );
        $collection = new AdjustedOpeningHoursCollection($entry);

        $updatedCalendar = $calendar->withAdjustedOpeningHours($collection);

        $this->assertFalse($updatedCalendar->getAdjustedOpeningHours()->isEmpty());
        $this->assertEquals(1, $updatedCalendar->getAdjustedOpeningHours()->count());

        $array = $updatedCalendar->getAdjustedOpeningHours()->toArray();
        $this->assertSame($entry, $array[0]);
    }

    /**
     * @test
     */
    public function it_allows_replacing_adjusted_opening_hours(): void
    {
        $calendar = new PermanentCalendar(new OpeningHours());

        $openingHours = new OpeningHours(
            new OpeningHour(new Days(Day::monday()), Time::fromString('09:00'), Time::fromString('17:00'))
        );
        $entry1 = new AdjustedOpeningHours(
            new DateTimeImmutable('2026-12-25'),
            new DateTimeImmutable('2026-12-26'),
            $openingHours
        );
        $collection1 = new AdjustedOpeningHoursCollection($entry1);

        $calendar = $calendar->withAdjustedOpeningHours($collection1);
        $this->assertEquals(1, $calendar->getAdjustedOpeningHours()->count());

        $entry2 = new AdjustedOpeningHours(
            new DateTimeImmutable('2026-01-01'),
            new DateTimeImmutable('2026-01-02'),
            $openingHours
        );
        $entry3 = new AdjustedOpeningHours(
            new DateTimeImmutable('2026-07-21'),
            new DateTimeImmutable('2026-07-22'),
            $openingHours
        );
        $collection2 = new AdjustedOpeningHoursCollection($entry2, $entry3);

        $updatedCalendar = $calendar->withAdjustedOpeningHours($collection2);
        $this->assertEquals(2, $updatedCalendar->getAdjustedOpeningHours()->count());

        // Original calendar should be unchanged
        $this->assertEquals(1, $calendar->getAdjustedOpeningHours()->count());
    }

    /**
     * @test
     */
    public function it_has_empty_closed_days_by_default(): void
    {
        $calendar = new PermanentCalendar(new OpeningHours());

        $this->assertTrue($calendar->getClosedDays()->isEmpty());
        $this->assertEquals(0, $calendar->getClosedDays()->count());
    }

    /**
     * @test
     */
    public function it_allows_setting_closed_days(): void
    {
        $calendar = new PermanentCalendar(new OpeningHours());

        $closedDay = new ClosedDay(
            new DateTimeImmutable('2024-12-25'),
            new DateTimeImmutable('2024-12-25')
        );
        $closedDays = new ClosedDays($closedDay);

        $updatedCalendar = $calendar->withClosedDays($closedDays);

        $this->assertFalse($updatedCalendar->getClosedDays()->isEmpty());
        $this->assertEquals(1, $updatedCalendar->getClosedDays()->count());

        $array = $updatedCalendar->getClosedDays()->toArray();
        $this->assertSame($closedDay, $array[0]);
    }

    /**
     * @test
     */
    public function it_allows_replacing_closed_days(): void
    {
        $calendar = new PermanentCalendar(new OpeningHours());

        $closedDay1 = new ClosedDay(
            new DateTimeImmutable('2024-12-25'),
            new DateTimeImmutable('2024-12-25')
        );
        $closedDays1 = new ClosedDays($closedDay1);

        $calendar = $calendar->withClosedDays($closedDays1);
        $this->assertEquals(1, $calendar->getClosedDays()->count());

        $closedDay2 = new ClosedDay(
            new DateTimeImmutable('2024-01-01'),
            new DateTimeImmutable('2024-01-01')
        );
        $closedDay3 = new ClosedDay(
            new DateTimeImmutable('2024-07-21'),
            new DateTimeImmutable('2024-07-21')
        );
        $closedDays2 = new ClosedDays($closedDay2, $closedDay3);

        $updatedCalendar = $calendar->withClosedDays($closedDays2);
        $this->assertEquals(2, $updatedCalendar->getClosedDays()->count());

        // Original calendar should be unchanged
        $this->assertEquals(1, $calendar->getClosedDays()->count());
    }
}
