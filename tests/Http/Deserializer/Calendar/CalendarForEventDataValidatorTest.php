<?php

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\UDB3\Deserializer\DataValidationException;
use PHPUnit\Framework\TestCase;

class CalendarForEventDataValidatorTest extends TestCase
{
    /**
     * @var CalendarForEventDataValidator
     */
    private $calendarForEventDataValidator;

    protected function setUp(): void
    {
        $this->calendarForEventDataValidator = new CalendarForEventDataValidator();
    }

    /**
     * @test
     * @dataProvider fileDataProvider
     */
    public function it_does_not_throw_for_valid_calendars(string $file): void
    {
        $data = json_decode(
            file_get_contents(__DIR__ . '/samples/' . $file),
            true
        );

        $this->calendarForEventDataValidator->validate($data);
        $this->addToAssertionCount(1);
    }

    public function fileDataProvider():array
    {
        return [
            'single_time_span' => [
                'file' => 'calendar_with_single_time_span_and_start_and_end.json',
            ],
            'multiple_time_span' => [
                'file' => 'calendar_with_multiple_time_spans_and_start_and_end.json',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function it_throws_when_invalid_data_is_present(
        array $data,
        array $messages
    ): void {
        $expectedException = new DataValidationException();
        $expectedException->setValidationMessages($messages);

        try {
            $this->calendarForEventDataValidator->validate($data);
            $this->fail("No DataValidationException was thrown.");
        } catch (DataValidationException $exception) {
            $this->assertEquals(
                $expectedException->getValidationMessages(),
                $exception->getValidationMessages()
            );
        }
    }

    public function dataProvider(): array
    {
        return [
            'it_throws_when_permanent' => [
                'data' => [
                ],
                'messages' => [
                    'permanent' => 'Permanent events are not supported.',
                ],
            ],
            'it_throws_on_invalid_top_level_status' => [
                'data' => [
                    'status' => [],
                ],
                'messages' => [
                    'status' => [
                        'type' => 'Required but could not be found',
                    ],
                ],
            ],
            'it_throws_when_end_date_is_missing' => [
                'data' => [
                    'startDate' => '2020-01-26T09:00:00+01:00',
                ],
                'messages' => [
                    'end_date' => 'When a start date is given then an end date is also required.',
                ],
            ],
            'it_throws_when_start_date_is_missing' => [
                'data' => [
                    'endDate' => '2020-02-10T16:00:00+01:00',
                ],
                'messages' => [
                    'start_date' => 'When an end date is given then a start date is also required.',
                ],
            ],
            'it_throws_when_end_date_is_before_start_date' => [
                'data' => [
                    'startDate' => '2020-02-10T16:00:00+01:00',
                    'endDate' => '2020-02-09T16:00:00+01:00',
                ],
                'messages' => [
                    'start_end_date' => 'The end date should be later then the start date.',
                ],
            ],
            'it_throws_when_time_span_has_missing_start' => [
                'data' => [
                    'timeSpans' => [
                        [
                            'end' => '2020-02-01T16:00:00+01:00',
                        ],
                    ],
                ],
                'messages' => [
                    'start_0' => 'A start is required for a time span.',
                ],
            ],
            'it_throws_when_time_span_has_missing_end' => [
                'data' => [
                    'timeSpans' => [
                        [
                            'start' => '2020-01-26T09:00:00+01:00',
                        ],
                    ],
                ],
                'messages' => [
                    'end_0' => 'An end is required for a time span.',
                ],
            ],
            'it_throws_on_invalid_status_inside_time_span' => [
                'data' => [
                    'timeSpans' => [
                        [
                            'start' => '2020-01-26T09:00:00+01:00',
                            'end' => '2020-02-01T16:00:00+01:00',
                            'status' => [],
                        ],
                    ],
                ],
                'messages' => [
                    'status_0' => [
                        'type' => 'Required but could not be found',
                    ],
                ],
            ],
            'it_throws_time_spans_and_opening_hours' => [
                'data' => [
                    'timeSpans' => [
                        [
                            'start' => '2020-01-26T09:00:00+01:00',
                            'end' => '2020-02-01T16:00:00+01:00',
                        ],
                        [
                            'start' => '2020-02-03T09:00:00+01:00',
                            'end' => '2020-02-10T16:00:00+01:00',
                        ],
                    ],
                    'openingHours' => [
                        [
                            'opens' => '09:00',
                            'closes' => '17:00',
                            'dayOfWeek' => [
                                'tuesday',
                                'wednesday',
                            ],
                        ],
                    ],
                ],
                'messages' => [
                    'opening_hours' => 'When opening hours are given no time spans are allowed.',
                ],
            ],
        ];
    }
}
