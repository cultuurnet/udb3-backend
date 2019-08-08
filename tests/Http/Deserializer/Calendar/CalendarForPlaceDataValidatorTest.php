<?php

namespace CultuurNet\UDB3\Http\Deserializer\Calendar;

use CultuurNet\Deserializer\DataValidationException;
use PHPUnit\Framework\TestCase;

class CalendarForPlaceDataValidatorTest extends TestCase
{
    /**
     * @var CalendarForPlaceDataValidator
     */
    private $calendarForPlaceDataValidator;

    protected function setUp()
    {
        $this->calendarForPlaceDataValidator = new CalendarForPlaceDataValidator();
    }

    /**
     * @test
     * @dataProvider dataProvider
     * @param array $data
     * @param array $messages
     */
    public function it_throws_when_time_spans_are_present(
        array $data,
        array $messages
    ) {
        $expectedException = new DataValidationException();
        $expectedException->setValidationMessages($messages);

        try {
            $this->calendarForPlaceDataValidator->validate($data);
            $this->fail("No DataValidationException was thrown.");
        } catch (\Exception $exception) {
            /* @var DataValidationException $exception */
            $this->assertInstanceOf(DataValidationException::class, $exception);
            $this->assertEquals(
                $expectedException->getValidationMessages(),
                $exception->getValidationMessages()
            );
        }
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [
            'it_throws_when_time_spans_are_present' => [
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
                    ]
                ],
                'messages' => [
                    'time_spans' => 'No time spans allowed for place calendar.',
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
        ];
    }
}
