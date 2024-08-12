<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\UDB3\Calendar\DayOfWeek;
use CultuurNet\UDB3\Calendar\DayOfWeekCollection;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Calendar\OpeningTime;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability;
use CultuurNet\UDB3\Calendar\Timestamp;
use PHPUnit\Framework\TestCase;

class CalendarJSONParserTest extends TestCase
{
    /**
     * @var array
     */
    private $updateCalendarAsArray;

    /**
     * @var CalendarJSONParser
     */
    private $calendarJSONParser;

    protected function setUp(): void
    {
        $updateCalendar = file_get_contents(__DIR__ . '/samples/calendar_all_fields.json');
        $this->updateCalendarAsArray = Json::decodeAssociatively($updateCalendar);

        $this->calendarJSONParser = new CalendarJSONParser();
    }

    /**
     * @test
     */
    public function it_can_get_the_start_date(): void
    {
        $startDate = DateTimeFactory::fromAtom('2020-01-26T09:00:00+01:00');

        $this->assertEquals(
            $startDate,
            $this->calendarJSONParser->getStartDate(
                $this->updateCalendarAsArray
            )
        );
    }

    /**
     * @test
     */
    public function it_returns_null_when_start_date_is_missing(): void
    {
        $updateCalendar = file_get_contents(__DIR__ . '/samples/calendar_missing_start_and_end.json');
        $updateCalendarAsArray = Json::decodeAssociatively($updateCalendar);

        $this->assertNull(
            $this->calendarJSONParser->getStartDate(
                $updateCalendarAsArray
            )
        );
    }

    /**
     * @test
     */
    public function it_can_get_the_end_date(): void
    {
        $endDate = DateTimeFactory::fromAtom('2020-02-10T16:00:00+01:00');

        $this->assertEquals(
            $endDate,
            $this->calendarJSONParser->getEndDate(
                $this->updateCalendarAsArray
            )
        );
    }

    /**
     * @test
     */
    public function it_returns_null_when_end_date_is_missing(): void
    {
        $updateCalendar = file_get_contents(__DIR__ . '/samples/calendar_missing_start_and_end.json');
        $updateCalendarAsArray = Json::decodeAssociatively($updateCalendar);

        $this->assertNull(
            $this->calendarJSONParser->getEndDate(
                $updateCalendarAsArray
            )
        );
    }

    /**
     * @test
     */
    public function it_can_get_the_status(): void
    {
        $status = new Status(
            StatusType::temporarilyUnavailable(),
            [
                new StatusReason(new Language('nl'), 'Reason in het Nederlands'),
                new StatusReason(new Language('fr'), 'Reason in het Frans'),
            ]
        );

        $this->assertEquals(
            $status,
            $this->calendarJSONParser->getStatus($this->updateCalendarAsArray)
        );
    }

    /**
     * @test
     */
    public function it_can_get_the_booking_availability(): void
    {
        $this->assertEquals(
            BookingAvailability::unavailable(),
            $this->calendarJSONParser->getBookingAvailability($this->updateCalendarAsArray)
        );
    }

    /**
     * @test
     */
    public function it_can_get_the_timestamps(): void
    {
        $startDatePeriod1 = DateTimeFactory::fromAtom('2020-01-26T09:00:00+01:00');
        $endDatePeriod1 = DateTimeFactory::fromAtom('2020-02-01T16:00:00+01:00');

        $startDatePeriod2 = DateTimeFactory::fromAtom('2020-02-03T09:00:00+01:00');
        $endDatePeriod2 = DateTimeFactory::fromAtom('2020-02-10T16:00:00+01:00');

        $timestamps = [
            (new Timestamp(
                $startDatePeriod1,
                $endDatePeriod1
            ))->withStatus(
                new Status(
                    StatusType::temporarilyUnavailable(),
                    [
                        new StatusReason(new Language('nl'), 'Reason in het Nederlands'),
                        new StatusReason(new Language('fr'), 'Reason in het Frans'),
                    ]
                )
            )->withBookingAvailability(BookingAvailability::unavailable()),
            (new Timestamp(
                $startDatePeriod2,
                $endDatePeriod2
            ))->withStatus(
                new Status(
                    StatusType::unavailable(),
                    [
                        new StatusReason(new Language('nl'), 'Reason in het Nederlands'),
                        new StatusReason(new Language('fr'), 'Reason in het Frans'),
                    ]
                )
            )->withBookingAvailability(BookingAvailability::unavailable()),
        ];

        $this->assertEquals(
            $timestamps,
            $this->calendarJSONParser->getTimestamps(
                $this->updateCalendarAsArray
            )
        );
    }

    /**
     * @test
     */
    public function it_should_not_create_timestamps_when_json_is_missing_an_end_property(): void
    {
        $calendarData = Json::decodeAssociatively(
            file_get_contents(__DIR__ . '/samples/calendar_missing_time_span_end.json')
        );

        $this->assertEmpty(
            $this->calendarJSONParser->getTimestamps($calendarData)
        );
    }

    /**
     * @test
     */
    public function it_should_not_create_timestamps_when_json_is_missing_a_start_property(): void
    {
        $calendarData = Json::decodeAssociatively(
            file_get_contents(__DIR__ . '/samples/calendar_missing_time_span_start.json')
        );

        $this->assertEmpty(
            $this->calendarJSONParser->getTimestamps($calendarData)
        );
    }

    /**
     * @test
     */
    public function it_can_get_the_opening_hours(): void
    {
        $openingHours = [
            new OpeningHour(
                new OpeningTime(
                    new Hour(9),
                    new Minute(0)
                ),
                new OpeningTime(
                    new Hour(17),
                    new Minute(0)
                ),
                new DayOfWeekCollection(
                    DayOfWeek::TUESDAY(),
                    DayOfWeek::WEDNESDAY(),
                    DayOfWeek::THURSDAY(),
                    DayOfWeek::FRIDAY()
                )
            ),
            new OpeningHour(
                new OpeningTime(
                    new Hour(9),
                    new Minute(0)
                ),
                new OpeningTime(
                    new Hour(12),
                    new Minute(0)
                ),
                new DayOfWeekCollection(
                    DayOfWeek::SATURDAY()
                )
            ),
        ];

        $this->assertEquals(
            $openingHours,
            $this->calendarJSONParser->getOpeningHours(
                $this->updateCalendarAsArray
            )
        );
    }

    /**
     * @test
     */
    public function it_should_not_create_opening_hours_when_fields_are_missing(): void
    {
        $calendarData = Json::decodeAssociatively(
            file_get_contents(__DIR__ . '/samples/calendar_with_opening_hours_but_missing_fields.json')
        );

        $this->assertEmpty(
            $this->calendarJSONParser->getOpeningHours($calendarData)
        );
    }
}
