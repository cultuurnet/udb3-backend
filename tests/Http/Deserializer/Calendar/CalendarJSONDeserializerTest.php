<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Calendar\DayOfWeek;
use CultuurNet\UDB3\Calendar\DayOfWeekCollection;
use CultuurNet\UDB3\Calendar\OpeningHour;
use CultuurNet\UDB3\Calendar\OpeningTime;
use CultuurNet\UDB3\Calendar\CalendarType;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Http\Deserializer\DataValidator\DataValidatorInterface;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusReason;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Offer\ValueObjects\BookingAvailability as LegacyBookingAvailability;
use CultuurNet\UDB3\Calendar\Timestamp;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CalendarJSONDeserializerTest extends TestCase
{
    /**
     * @var DataValidatorInterface&MockObject
     */
    private $calendarDataValidator;

    protected function setUp(): void
    {
        $this->calendarDataValidator = $this->createMock(DataValidatorInterface::class);
    }

    /**
     * @test
     */
    public function it_can_deserialize_json_to_calendar(): void
    {
        $calendarAsJsonString = SampleFiles::read(__DIR__ . '/samples/calendar.json');

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
            DateTimeFactory::fromAtom('2020-01-26T09:00:00+01:00'),
            DateTimeFactory::fromAtom('2020-02-10T16:00:00+01:00'),
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
    public function it_can_deserialize_json_to_calendar_with_status(): void
    {
        $calendarAsJsonString = SampleFiles::read(__DIR__ . '/samples/calendar_with_status.json');

        $calendarJSONDeserializer = new CalendarJSONDeserializer(
            new CalendarJSONParser(),
            $this->calendarDataValidator
        );

        $expectedCalendar = new Calendar(
            CalendarType::PERIODIC(),
            DateTimeFactory::fromAtom('2020-01-26T09:00:00+01:00'),
            DateTimeFactory::fromAtom('2020-02-10T16:00:00+01:00')
        );

        $expectedCalendar = $expectedCalendar->withStatus(
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
        );

        $this->assertEquals(
            $expectedCalendar,
            $calendarJSONDeserializer->deserialize($calendarAsJsonString)
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_json_to_calendar_with_booking_availability(): void
    {
        $calendarAsJsonString = SampleFiles::read(__DIR__ . '/samples/calendar_with_booking_availability.json');

        $calendarJSONDeserializer = new CalendarJSONDeserializer(
            new CalendarJSONParser(),
            $this->calendarDataValidator
        );

        $expectedCalendar = (new Calendar(
            CalendarType::SINGLE(),
            null,
            null,
            [
                (new TimeStamp(
                    DateTimeFactory::fromAtom('2020-02-03T09:00:00+01:00'),
                    DateTimeFactory::fromAtom('2020-02-10T16:00:00+01:00')
                ))->withBookingAvailability(BookingAvailability::Unavailable()),
            ]
        ))->withBookingAvailability(LegacyBookingAvailability::unavailable());

        $this->assertEquals(
            $expectedCalendar,
            $calendarJSONDeserializer->deserialize($calendarAsJsonString)
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_json_to_calendar_with_status_on_time_spans(): void
    {
        $calendarAsJsonString = SampleFiles::read(__DIR__ . '/samples/calendar_with_status_on_time_spans.json');

        $calendarJSONDeserializer = new CalendarJSONDeserializer(
            new CalendarJSONParser(),
            $this->calendarDataValidator
        );

        $startDate1 = DateTimeFactory::fromAtom('2020-01-26T09:00:00+01:00');
        $endDate1 = DateTimeFactory::fromAtom('2020-02-01T16:00:00+01:00');

        $startDate2 = DateTimeFactory::fromAtom('2020-02-03T09:00:00+01:00');
        $endDate2 = DateTimeFactory::fromAtom('2020-02-10T16:00:00+01:00');

        $timestamps = [
            (new Timestamp(
                $startDate1,
                $endDate1
            ))->withStatus(
                new Status(
                    StatusType::TemporarilyUnavailable(),
                    (new TranslatedStatusReason(
                        new Language('nl'),
                        new StatusReason('TemporarilyUnavailable in het Nederlands')
                    ))->withTranslation(
                        new Language('fr'),
                        new StatusReason('TemporarilyUnavailable in het Frans')
                    )
                )
            ),
            (new Timestamp(
                $startDate2,
                $endDate2
            ))->withStatus(
                new Status(
                    StatusType::Unavailable(),
                    (new TranslatedStatusReason(
                        new Language('nl'),
                        new StatusReason('Unavailable in het Nederlands')
                    ))->withTranslation(
                        new Language('fr'),
                        new StatusReason('Unavailable in het Frans')
                    )
                )
            ),
        ];

        $expectedCalendar = new Calendar(
            CalendarType::MULTIPLE(),
            null,
            null,
            $timestamps
        );

        $expectedCalendar = $expectedCalendar->withStatus(
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
        );

        $this->assertEquals(
            $expectedCalendar,
            $calendarJSONDeserializer->deserialize($calendarAsJsonString)
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_json_to_calendar_with_booking_availability_on_time_spans(): void
    {
        $calendarAsJsonString = SampleFiles::read(__DIR__ . '/samples/calendar_with_booking_availability_on_time_spans.json');

        $calendarJSONDeserializer = new CalendarJSONDeserializer(
            new CalendarJSONParser(),
            $this->calendarDataValidator
        );

        $startDate1 = DateTimeFactory::fromAtom('2020-01-26T09:00:00+01:00');
        $endDate1 = DateTimeFactory::fromAtom('2020-02-01T16:00:00+01:00');

        $startDate2 = DateTimeFactory::fromAtom('2020-02-03T09:00:00+01:00');
        $endDate2 = DateTimeFactory::fromAtom('2020-02-10T16:00:00+01:00');

        $timestamps = [
            (new Timestamp(
                $startDate1,
                $endDate1
            ))->withBookingAvailability(BookingAvailability::Unavailable()),
            (new Timestamp(
                $startDate2,
                $endDate2
            ))->withBookingAvailability(BookingAvailability::Unavailable()),
        ];

        $expectedCalendar = (new Calendar(
            CalendarType::MULTIPLE(),
            null,
            null,
            $timestamps
        ))->withBookingAvailability(LegacyBookingAvailability::unavailable());

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
    ): void {
        $calendarAsJsonString = $calendarData;

        $calendarJSONDeserializer = new CalendarJSONDeserializer(
            new CalendarJSONParser(),
            $this->calendarDataValidator
        );

        $calendar = $calendarJSONDeserializer->deserialize($calendarAsJsonString);

        $this->assertEquals($expectedCalendarType, $calendar->getType());
    }

    public function calendarDataProvider(): array
    {
        return [
            'calendar_of_type_PERMANENT_when_json_only_contains_opening_hours' => [
                'calendarData' => SampleFiles::read(__DIR__ . '/samples/calendar_with_opening_hours.json'),
                'expectedCalendarType' => CalendarType::PERMANENT(),
            ],
            'calendar_of_type_PERMANENT_when_json_is_empty' => [
                'calendarData' => SampleFiles::read(__DIR__ . '/samples/empty_calendar.json'),
                'expectedCalendarType' => CalendarType::PERMANENT(),
            ],
            'calendar_of_type_SINGLE_when_json_contains_a_single_time_span' => [
                'calendarData' => SampleFiles::read(__DIR__ . '/samples/calendar_with_single_time_span.json'),
                'expectedCalendarType' => CalendarType::SINGLE(),
            ],
            'calendar_of_type_MULTIPLE_when_json_contains_multiple_time_spans' => [
                'calendarData' => SampleFiles::read(__DIR__ . '/samples/calendar_with_multiple_time_spans.json'),
                'expectedCalendarType' => CalendarType::MULTIPLE(),
            ],
            'calendar_of_type_PERIODIC_when_json_contains_start_and_end_date' => [
                'calendarData' => SampleFiles::read(__DIR__ . '/samples/calendar_with_start_and_end_date.json'),
                'expectedCalendarType' => CalendarType::PERIODIC(),
            ],
        ];
    }
}
