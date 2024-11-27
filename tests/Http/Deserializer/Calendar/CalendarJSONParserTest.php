<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\DateRange;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusReason;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEvent;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\TestCase;

class CalendarJSONParserTest extends TestCase
{
    private array $updateCalendarAsArray;

    private CalendarJSONParser $calendarJSONParser;

    protected function setUp(): void
    {
        $updateCalendar = SampleFiles::read(__DIR__ . '/samples/calendar_all_fields.json');
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
        $updateCalendar = SampleFiles::read(__DIR__ . '/samples/calendar_missing_start_and_end.json');
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
        $updateCalendar = SampleFiles::read(__DIR__ . '/samples/calendar_missing_start_and_end.json');
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
            StatusType::TemporarilyUnavailable(),
            (new TranslatedStatusReason(
                new Language('nl'),
                new StatusReason('Reason in het Nederlands')
            ))->withTranslation(
                new Language('fr'),
                new StatusReason('Reason in het Frans')
            )
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
            BookingAvailability::Unavailable(),
            $this->calendarJSONParser->getBookingAvailability($this->updateCalendarAsArray)
        );
    }

    /**
     * @test
     */
    public function it_can_get_the_sub_events(): void
    {
        $startDatePeriod1 = DateTimeFactory::fromAtom('2020-01-26T09:00:00+01:00');
        $endDatePeriod1 = DateTimeFactory::fromAtom('2020-02-01T16:00:00+01:00');

        $startDatePeriod2 = DateTimeFactory::fromAtom('2020-02-03T09:00:00+01:00');
        $endDatePeriod2 = DateTimeFactory::fromAtom('2020-02-10T16:00:00+01:00');

        $subEvents = [
            (SubEvent::createAvailable(
                new DateRange($startDatePeriod1, $endDatePeriod1)
            ))->withStatus(
                new Status(
                    StatusType::TemporarilyUnavailable(),
                    (new TranslatedStatusReason(
                        new Language('nl'),
                        new StatusReason('Reason in het Nederlands')
                    ))->withTranslation(
                        new Language('fr'),
                        new StatusReason('Reason in het Frans')
                    )
                )
            )->withBookingAvailability(BookingAvailability::Unavailable()),
            (SubEvent::createAvailable(
                new DateRange($startDatePeriod2, $endDatePeriod2)
            ))->withStatus(
                new Status(
                    StatusType::Unavailable(),
                    (new TranslatedStatusReason(
                        new Language('nl'),
                        new StatusReason('Reason in het Nederlands')
                    ))->withTranslation(
                        new Language('fr'),
                        new StatusReason('Reason in het Frans')
                    )
                )
            )->withBookingAvailability(BookingAvailability::Unavailable()),
        ];

        $this->assertEquals(
            $subEvents,
            $this->calendarJSONParser->getSubEvents(
                $this->updateCalendarAsArray
            )
        );
    }

    /**
     * @test
     */
    public function it_should_not_create_sub_events_when_json_is_missing_an_end_property(): void
    {
        $calendarData = Json::decodeAssociatively(
            SampleFiles::read(__DIR__ . '/samples/calendar_missing_time_span_end.json')
        );

        $this->assertEmpty(
            $this->calendarJSONParser->getSubEvents($calendarData)
        );
    }

    /**
     * @test
     */
    public function it_should_not_create_sub_events_when_json_is_missing_a_start_property(): void
    {
        $calendarData = Json::decodeAssociatively(
            SampleFiles::read(__DIR__ . '/samples/calendar_missing_time_span_start.json')
        );

        $this->assertEmpty(
            $this->calendarJSONParser->getSubEvents($calendarData)
        );
    }

    /**
     * @test
     */
    public function it_can_get_the_opening_hours(): void
    {
        $openingHours = [
            new OpeningHour(
                new Days(
                    Day::tuesday(),
                    Day::wednesday(),
                    Day::thursday(),
                    Day::friday()
                ),
                new Time(
                    new Hour(9),
                    new Minute(0)
                ),
                new Time(
                    new Hour(17),
                    new Minute(0)
                )
            ),
            new OpeningHour(
                new Days(
                    Day::saturday()
                ),
                new Time(
                    new Hour(9),
                    new Minute(0)
                ),
                new Time(
                    new Hour(12),
                    new Minute(0)
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
            SampleFiles::read(__DIR__ . '/samples/calendar_with_opening_hours_but_missing_fields.json')
        );

        $this->assertEmpty(
            $this->calendarJSONParser->getOpeningHours($calendarData)
        );
    }
}
