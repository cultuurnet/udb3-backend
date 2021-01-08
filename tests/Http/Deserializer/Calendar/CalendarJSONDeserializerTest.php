<?php

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\Calendar\DayOfWeek;
use CultuurNet\UDB3\Calendar\DayOfWeekCollection;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Calendar\OpeningTime;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Language;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\DateTime\Hour;
use ValueObjects\DateTime\Minute;
use ValueObjects\StringLiteral\StringLiteral;

class CalendarJSONDeserializerTest extends TestCase
{
    /**
     * @var DataValidatorInterface|MockObject
     */
    private $calendarDataValidator;

    protected function setUp()
    {
        $this->calendarDataValidator = $this->createMock(DataValidatorInterface::class);
    }

    /**
     * @test
     */
    public function it_can_deserialize_json_to_calendar()
    {
        $calendarAsJsonString = new StringLiteral(
            file_get_contents(__DIR__ . '/samples/calendar.json')
        );

        $calendarJSONDeserializer = new CalendarJSONDeserializer(
            new CalendarJSONParser(),
            $this->calendarDataValidator
        );

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

        $expectedCalendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-26T09:00:00+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-02-10T16:00:00+01:00'),
            [],
            $openingHours
        );

        $this->assertEquals(
            $expectedCalendar,
            $calendarJSONDeserializer->deserialize($calendarAsJsonString)
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_json_to_calendar_with_status()
    {
        $calendarAsJsonString = new StringLiteral(
            file_get_contents(__DIR__ . '/samples/calendar_with_status.json')
        );

        $calendarJSONDeserializer = new CalendarJSONDeserializer(
            new CalendarJSONParser(),
            $this->calendarDataValidator
        );

        $expectedCalendar = new Calendar(
            CalendarType::PERIODIC(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-01-26T09:00:00+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2020-02-10T16:00:00+01:00')
        );

        $expectedCalendar = $expectedCalendar->withStatus(
            new Status(
                StatusType::unavailable(),
                [
                    new StatusReason(new Language('nl'), 'Reason in het Nederlands'),
                    new StatusReason(new Language('fr'), 'Reason in het Frans'),
                ]
            )
        );

        $this->assertEquals(
            $expectedCalendar,
            $calendarJSONDeserializer->deserialize($calendarAsJsonString)
        );
    }

    /**
     * @test
     * @dataProvider calendarDataProvider()
     */
    public function it_should_return_right_calendar_type_from_json_data(
        string $calendarData,
        CalendarType $expectedCalendarType
    ) {
        $calendarAsJsonString = new StringLiteral($calendarData);

        $calendarJSONDeserializer = new CalendarJSONDeserializer(
            new CalendarJSONParser(),
            $this->calendarDataValidator
        );

        $calendar = $calendarJSONDeserializer->deserialize($calendarAsJsonString);

        $this->assertEquals($expectedCalendarType, $calendar->getType());
    }

    public function calendarDataProvider()
    {
        return [
            'calendar_of_type_PERMANENT_when_json_only_contains_opening_hours' => [
                'calendarData' => file_get_contents(__DIR__ . '/samples/calendar_with_opening_hours.json'),
                'expectedCalendarType' => CalendarType::PERMANENT(),
            ],
            'calendar_of_type_PERMANENT_when_json_is_empty' => [
                'calendarData' => file_get_contents(__DIR__ . '/samples/empty_calendar.json'),
                'expectedCalendarType' => CalendarType::PERMANENT(),
            ],
            'calendar_of_type_SINGLE_when_json_contains_a_single_time_span' => [
                'calendarData' => file_get_contents(__DIR__ . '/samples/calendar_with_single_time_span.json'),
                'expectedCalendarType' => CalendarType::SINGLE(),
            ],
            'calendar_of_type_MULTIPLE_when_json_contains_multiple_time_spans' => [
                'calendarData' => file_get_contents(__DIR__ . '/samples/calendar_with_multiple_time_spans.json'),
                'expectedCalendarType' => CalendarType::MULTIPLE(),
            ],
            'calendar_of_type_PERIODIC_when_json_contains_start_and_end_date' => [
                'calendarData' => file_get_contents(__DIR__ . '/samples/calendar_with_start_and_end_date.json'),
                'expectedCalendarType' => CalendarType::PERIODIC(),
            ],
        ];
    }
}
