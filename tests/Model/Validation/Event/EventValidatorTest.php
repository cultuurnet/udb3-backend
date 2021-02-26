<?php

namespace CultuurNet\UDB3\Model\Validation\Event;

use PHPUnit\Framework\TestCase;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Validator;

class EventValidatorTest extends TestCase
{
    /**
     * @var EventValidator
     */
    private $validator;

    public function setUp()
    {
        $this->validator = new EventValidator();
    }

    /**
     * @test
     */
    public function it_should_pass_all_required_properties_are_present_in_a_valid_format()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_required_property_is_missing()
    {
        $event = [];

        $expectedErrors = [
            'Key @id must be present',
            'Key mainLanguage must be present',
            'Key name must be present',
            'Key terms must be present',
            'Key calendarType must be present',
            'Key location must be present',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_id_is_in_an_invalid_format()
    {
        $event = [
            '@id' => 'http://io.uitdatabank.be/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test event',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        // @codingStandardsIgnoreStart
        $expectedErrors = [
            '@id must validate against "/\\\/event[s]?\\\/([0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-?[0-9A-Fa-f]{12})[\\\/]?/"',
        ];
        // @codingStandardsIgnoreEnd

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_mainLanguage_is_in_an_invalid_format()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'foo',
            'name' => [
                'nl' => 'Test event',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'mainLanguage must validate against "/^[a-z]{2}$/"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_name_has_no_entries()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'name must have a length greater than 1',
            'name must have a value for the mainLanguage (nl)',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_name_translation_is_empty()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test event',
                'fr' => '',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'name value must not be empty',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_name_translation_has_an_invalid_language()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Test event',
                'foo' => 'Test event',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            '"foo" must validate against "/^[a-z]{2}$/"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_name_is_a_string()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => 'Example name',
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'name must be of the type array',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_has_an_unknown_value()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'foobar',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'At least one of these rules must pass for calendarType',
            'calendarType must be equal to "single"',
            'calendarType must be equal to "multiple"',
            'calendarType must be equal to "periodic"',
            'calendarType must be equal to "permanent"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_single_and_required_fields_are_missing()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'single',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'These rules must pass for calendarType single',
            'Key startDate must be present',
            'Key endDate must be present',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_single_and_startDate_or_endDate_is_malformed()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'single',
            'startDate' => '12/01/2018',
            'endDate' => '13/01/2018',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'These rules must pass for calendarType single',
            'startDate must be a valid date. Sample format: "2005-12-30T01:02:03+01:00"',
            'endDate must be a valid date. Sample format: "2005-12-30T01:02:03+01:00"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_single_and_endDate_is_before_startDate()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'single',
            'startDate' => '2018-03-05T13:44:09+01:00',
            'endDate' => '2018-02-28T13:44:09+01:00',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'endDate must be greater than or equal to "2018-03-05T13:44:09+01:00"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_single_and_the_sub_event_status_is_invalid()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'single',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-28T13:44:09+01:00',
                    'endDate' => '2018-03-01T13:44:09+01:00',
                    'status' => 'should not be a string',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'status must be of the type array',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_single_and_there_are_multiple_subEvents()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'single',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-28T13:44:09+01:00',
                    'endDate' => '2018-03-01T13:44:09+01:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-03-04T13:44:09+01:00',
                    'endDate' => '2018-03-05T13:44:09+01:00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'calendarType single should have exactly one subEvent',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_multiple_and_required_fields_are_missing()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'multiple',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'These rules must pass for calendarType multiple',
            'Key startDate must be present',
            'Key endDate must be present',
            'Key subEvent must be present',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_multiple_and_a_subEvent_is_missing_required_fields()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'multiple',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-01T13:44:09+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-28T13:44:09+01:00',
                ],
                [
                    '@type' => 'Event',
                    'endDate' => '2018-03-01T13:44:09+01:00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'Each item in subEvent must be valid',
            'Key endDate must be present',
            'Key startDate must be present',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_multiple_and_there_is_only_one_subEvent()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'multiple',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-01T13:44:09+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-28T13:44:09+01:00',
                    'endDate' => '2018-03-01T13:44:09+01:00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'subEvent must have at least 2 subEvent(s)',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_multiple_and_a_subEvent_has_a_malformed_date()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'multiple',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-28T13:44:09+01:00',
                    'endDate' => '2018-03-01',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-03-04',
                    'endDate' => '2018-03-05T13:44:09+01:00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'Each item in subEvent must be valid',
            'endDate must be a valid date. Sample format: "2005-12-30T01:02:03+01:00"',
            'startDate must be a valid date. Sample format: "2005-12-30T01:02:03+01:00"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_multiple_and_a_subEvent_has_an_invalid_status()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'multiple',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-24T13:44:09+01:00',
                    'endDate' => '2018-02-24T15:44:09+01:00',
                    'status' => 'Available',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-24T13:44:09+01:00',
                    'endDate' => '2018-02-24T15:44:09+01:00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'status must be of the type array',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_multiple_and_a_subEvent_has_an_invalid_status_type()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'multiple',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-24T13:44:09+01:00',
                    'endDate' => '2018-02-24T15:44:09+01:00',
                    'status' => [
                        'type' => 'Available',
                    ],
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-26T13:44:09+01:00',
                    'endDate' => '2018-02-26T15:44:09+01:00',
                    'status' => [
                        'type' => 'foo',
                    ],
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-28T13:44:09+01:00',
                    'endDate' => '2018-02-28T15:44:09+01:00',
                    'status' => [
                        'type' => 'Unavailable',
                    ],
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'At least one of these rules must pass for type',
            'type must be equal to "Available"',
            'type must be equal to "TemporarilyUnavailable"',
            'type must be equal to "Unavailable"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_multiple_and_a_subEvent_has_a_string_reason()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'multiple',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-24T13:44:09+01:00',
                    'endDate' => '2018-02-24T15:44:09+01:00',
                    'status' => [
                        'type' => 'Available',
                    ],
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-26T13:44:09+01:00',
                    'endDate' => '2018-02-26T15:44:09+01:00',
                    'status' => [
                        'type' => 'TemporarilyUnavailable',
                        'reason' => 'This should be an object instead of a string.',
                    ],
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-28T13:44:09+01:00',
                    'endDate' => '2018-02-28T15:44:09+01:00',
                    'status' => [
                        'type' => 'Unavailable',
                    ],
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'reason must be of the type array',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_multiple_and_subEvent_has_invalid_reason_language()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'multiple',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-24T13:44:09+01:00',
                    'endDate' => '2018-02-24T15:44:09+01:00',
                    'status' => [
                        'type' => 'Available',
                    ],
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-26T13:44:09+01:00',
                    'endDate' => '2018-02-26T15:44:09+01:00',
                    'status' => [
                        'type' => 'TemporarilyUnavailable',
                        'reason' => [
                            0 => 'Should be keyed by language',
                            'Invalid language' => 'Invalid language key',
                            'nl' => '',
                        ],
                    ],
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-28T13:44:09+01:00',
                    'endDate' => '2018-02-28T15:44:09+01:00',
                    'status' => [
                        'type' => 'Unavailable',
                    ],
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'Each item in { "Should be keyed by language", "Invalid language": "Invalid language key", "nl": "" } ' .
                'must be valid',
            '0 must validate against "/^[a-z]{2}$/"',
            '"Invalid language" must validate against "/^[a-z]{2}$/"',
            'reason value must not be empty',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_multiple_and_endDate_is_after_startDate()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'multiple',
            'startDate' => '2018-03-05T13:44:09+01:00',
            'endDate' => '2018-02-28T13:44:09+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-28T13:44:09+01:00',
                    'endDate' => '2018-03-01T13:44:09+01:00',
                ],
                [
                    '@type' => 'Event',
                    'startDate' => '2018-03-04T13:44:09+01:00',
                    'endDate' => '2018-03-05T13:44:09+01:00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'endDate must be greater than or equal to "2018-03-05T13:44:09+01:00"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_periodic_and_required_fields_are_missing()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'periodic',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'These rules must pass for calendarType periodic',
            'Key startDate must be present',
            'Key endDate must be present',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_periodic_and_startDate_or_endDate_is_malformed()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'periodic',
            'startDate' => '12/01/2018',
            'endDate' => '13/01/2018',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'These rules must pass for calendarType periodic',
            'startDate must be a valid date. Sample format: "2005-12-30T01:02:03+01:00"',
            'endDate must be a valid date. Sample format: "2005-12-30T01:02:03+01:00"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_periodic_and_an_openingHour_misses_required_fields()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'periodic',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '08:00',
                ],
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'closes' => '16:00',
                ],
                [
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'Each item in openingHours must be valid',
            'Key closes must be present',
            'Key opens must be present',
            'Key dayOfWeek must be present',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_periodic_and_opens_or_closes_is_malformed()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'periodic',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '08h00',
                    'closes' => '16h00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'These rules must pass for openingHour',
            'opens must be a valid date. Sample format: "01:02"',
            'closes must be a valid date. Sample format: "01:02"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_periodic_and_dayOfWeek_is_not_an_array()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'periodic',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'openingHours' => [
                [
                    'dayOfWeek' => 'monday',
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'All of the required rules must pass for dayOfWeek',
            'dayOfWeek must be of the type array',
            'Each item in dayOfWeek must be valid',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_periodic_and_dayOfWeek_has_an_unknown_value()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'periodic',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday', 'wed'],
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'At least one of these rules must pass for dayOfWeek',
            'dayOfWeek must be equal to "monday"',
            'dayOfWeek must be equal to "tuesday"',
            'dayOfWeek must be equal to "wednesday"',
            'dayOfWeek must be equal to "thursday"',
            'dayOfWeek must be equal to "friday"',
            'dayOfWeek must be equal to "saturday"',
            'dayOfWeek must be equal to "sunday"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_permanent_and_openingHour_misses_required_fields()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '08:00',
                ],
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'closes' => '16:00',
                ],
                [
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'Each item in openingHours must be valid',
            'Key closes must be present',
            'Key opens must be present',
            'Key dayOfWeek must be present',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_permanent_and_opens_or_closes_is_malformed()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '08h00',
                    'closes' => '16h00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'These rules must pass for openingHour',
            'opens must be a valid date. Sample format: "01:02"',
            'closes must be a valid date. Sample format: "01:02"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_permanent_and_opens_is_after_closes()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday'],
                    'opens' => '16:00',
                    'closes' => '08:00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'closes must be greater than or equal to "16:00"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_permanent_and_dayOfWeek_is_not_an_array()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'openingHours' => [
                [
                    'dayOfWeek' => 'monday',
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'All of the required rules must pass for dayOfWeek',
            'dayOfWeek must be of the type array',
            'Each item in dayOfWeek must be valid',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_calendarType_is_permanent_and_dayOfWeek_has_an_unknown_value()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'openingHours' => [
                [
                    'dayOfWeek' => ['monday', 'tuesday', 'wed'],
                    'opens' => '08:00',
                    'closes' => '16:00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'At least one of these rules must pass for dayOfWeek',
            'dayOfWeek must be equal to "monday"',
            'dayOfWeek must be equal to "tuesday"',
            'dayOfWeek must be equal to "wednesday"',
            'dayOfWeek must be equal to "thursday"',
            'dayOfWeek must be equal to "friday"',
            'dayOfWeek must be equal to "saturday"',
            'dayOfWeek must be equal to "sunday"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_both_location_id_and_address_are_missing()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                'foo' => 'bar',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        // @codingStandardsIgnoreStart
        $expectedErrors = [
            'At least one of these rules must pass for location',
            'Key location @id must be present',
            'Key location address must be present',
        ];
        // @codingStandardsIgnoreEnd

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_location_id_is_in_an_invalid_format()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => '9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        // @codingStandardsIgnoreStart
        $expectedErrors = [
            'All of the required rules must pass for location @id',
            'location @id must be a URL',
            'location @id must validate against "/\\\/place[s]?\\\/([0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-?[0-9A-Fa-f]{12})[\\\/]?/"',
        ];
        // @codingStandardsIgnoreEnd

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_location_address_has_no_entries()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                'address' => [],
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'location address must have a length greater than 1',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_terms_is_empty()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [],
        ];

        $expectedErrors = [
            'terms must have at least 1 term(s)',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_term_is_missing_an_id()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'label' => 'foo',
                    'domain' => 'bar',
                ],
            ],
        ];

        $expectedErrors = [
            'Key term id must be present',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_term_has_an_id_that_is_not_a_string()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => 1,
                ],
            ],
        ];

        $expectedErrors = [
            'term id must be a string',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_audienceType_is_set_but_it_has_an_unknown_value()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'audience' => [
                'audienceType' => 'foo',
            ],
        ];

        $expectedErrors = [
            'At least one of these rules must pass for audience.audienceType',
            'audience.audienceType must be equal to "everyone"',
            'audience.audienceType must be equal to "members"',
            'audience.audienceType must be equal to "education"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_audienceType_is_set_to_a_known_value()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'audience' => [
                'audienceType' => 'everyone',
            ],
        ];

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_labels_is_set_but_not_an_array()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'audience' => [
                'audienceType' => 'everyone',
            ],
            'labels' => 'foo,bar',
        ];

        $expectedErrors = [
            'labels must be of the type array',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_labels_is_set_but_contains_something_different_than_a_string()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'audience' => [
                'audienceType' => 'everyone',
            ],
            'labels' => [
                ['name' => 'foo', 'visible' => true],
            ],
        ];

        $expectedErrors = [
            'each label must be a string',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_label_is_too_short()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'audience' => [
                'audienceType' => 'everyone',
            ],
            'labels' => [
                'f',
                'fo',
                'foo',
            ],
        ];

        $expectedErrors = [
            '"f" must have a length between 2 and 255',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_label_is_too_long()
    {
        // @codingStandardsIgnoreStart
        $longLabel = 'barbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbarbar';
        // @codingStandardsIgnoreEnd

        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'audience' => [
                'audienceType' => 'everyone',
            ],
            'labels' => [
                'foo',
                $longLabel,
            ],
        ];

        $expectedErrors = [
            "\"{$longLabel}\" must have a length between 2 and 255",
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_label_contains_point_comma()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'audience' => [
                'audienceType' => 'everyone',
            ],
            'labels' => [
                'foo',
                'b;r',
            ],
        ];

        $expectedErrors = [
            '"b;r" must not validate against "/;/"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_labels_is_an_array_of_strings()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'audience' => [
                'audienceType' => 'everyone',
            ],
            'labels' => [
                'foo',
                'bar',
            ],
        ];

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_hiddenLabels_is_set_but_not_an_array()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'audience' => [
                'audienceType' => 'everyone',
            ],
            'hiddenLabels' => 'foo,bar',
        ];

        $expectedErrors = [
            'hiddenLabels must be of the type array',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_hiddenLabels_is_set_but_contains_something_different_than_a_string()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'audience' => [
                'audienceType' => 'everyone',
            ],
            'hiddenLabels' => [
                ['name' => 'foo', 'visible' => true],
            ],
        ];

        $expectedErrors = [
            'each label must be a string',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_hiddenLabels_is_an_array_of_strings()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'audience' => [
                'audienceType' => 'everyone',
            ],
            'hiddenLabels' => [
                'foo',
                'bar',
            ],
        ];

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_description_has_no_entries()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'description' => [],
        ];

        $expectedErrors = [
            'description must have a length greater than 1',
            'description must have a value for the mainLanguage (nl)',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_description_translation_is_empty()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'description' => [
                'nl' => '',
            ],
        ];

        $expectedErrors = [
            'description value must not be empty',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_a_description_translation_has_an_invalid_language()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'description' => [
                'nl' => 'Test beschrijving',
                'foo' => 'Test description',
            ],
        ];

        $expectedErrors = [
            '"foo" must validate against "/^[a-z]{2}$/"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_description_is_a_string()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'description' => 'Test description',
        ];

        $expectedErrors = [
            'description must be of the type array',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_description_is_in_a_valid_format()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'description' => [
                'nl' => 'Test description',
            ],
        ];

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_status_is_invalid()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'status' => 'should not be a string',
            'calendarType' => 'single',
            'startDate' => '2018-02-28T13:44:09+01:00',
            'endDate' => '2018-03-05T13:44:09+01:00',
            'subEvent' => [
                [
                    '@type' => 'Event',
                    'startDate' => '2018-02-28T13:44:09+01:00',
                    'endDate' => '2018-03-01T13:44:09+01:00',
                ],
            ],
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
        ];

        $expectedErrors = [
            'status must be of the type array',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_organizer_id_is_in_an_invalid_format()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'organizer' => [
                '@id' => 'https://io.uitdatabank.be/e78befcb-d337-4646-a721-407f69f0ce22',
            ],
        ];

        // @codingStandardsIgnoreStart
        $expectedErrors = [
            'organizer @id must validate against "/\\\/organizer[s]?\\\/([0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-?[0-9A-Fa-f]{12})[\\\/]?/"',
        ];
        // @codingStandardsIgnoreEnd

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_organizer_id_is_in_a_valid_format()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'organizer' => [
                '@id' => 'https://io.uitdatabank.be/organizers/e78befcb-d337-4646-a721-407f69f0ce22',
            ],
        ];

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_typicalAgeRange_is_not_a_string()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'typicalAgeRange' => [
                'from' => 8,
                'to' => 12,
            ],
        ];

        // @codingStandardsIgnoreStart
        $expectedErrors = [
            'typicalAgeRange must be a string',
        ];
        // @codingStandardsIgnoreEnd

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_typicalAgeRange_is_not_formatted_correctly()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'typicalAgeRange' => '8 TO 12',
        ];

        // @codingStandardsIgnoreStart
        $expectedErrors = [
            '"8 TO 12" must validate against "/\\\A[\\\d]*-[\\\d]*\\\z/"',
        ];
        // @codingStandardsIgnoreEnd

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_typicalAgeRange_is_correctly_formatted()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'typicalAgeRange' => '8-12',
        ];

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_workflowStatus_is_an_unknown_value()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'workflowStatus' => 'foo',
        ];

        $expectedErrors = [
            'At least one of these rules must pass for workflowStatus',
            'workflowStatus must be equal to "READY_FOR_VALIDATION"',
            'workflowStatus must be equal to "APPROVED"',
            'workflowStatus must be equal to "REJECTED"',
            'workflowStatus must be equal to "DRAFT"',
            'workflowStatus must be equal to "DELETED"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_workflowStatus_has_a_valid_value()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'workflowStatus' => 'APPROVED',
        ];

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_availableFrom_is_in_an_invalid_format()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'availableFrom' => '05/03/2018',
        ];

        $expectedErrors = [
            'availableFrom must be a valid date. Sample format: "2005-12-30T01:02:03+01:00"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_availableFrom_is_an_ISO_8601_datetime()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'availableFrom' => '2018-03-05T13:44:09+01:00',
        ];

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_contactPoint_has_an_invalid_phone()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                    '',
                ],
                'email' => [
                    'info@publiq.be',
                    'foo@publiq.be',
                ],
                'url' => [
                    'https://www.publiq.be',
                    'https://www.uitdatabank.be',
                ],
            ],
        ];

        $expectedErrors = [
            'each phone must not be empty',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_contactPoint_has_an_invalid_email()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                ],
                'email' => [
                    'info@publiq.be',
                    'publiq.be',
                ],
                'url' => [
                    'https://www.publiq.be',
                    'https://www.uitdatabank.be',
                ],
            ],
        ];

        $expectedErrors = [
            'each email must be valid email',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_contactPoint_has_an_invalid_url()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                ],
                'email' => [
                    'info@publiq.be',
                ],
                'url' => [
                    'https://www.publiq.be',
                    'www.uitdatabank.be',
                ],
            ],
        ];

        $expectedErrors = [
            'each url must be a URL',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_contactPoint_has_valid_phone_numbers()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                    '02/551 18 70',
                ],
            ],
        ];

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_pass_if_contactPoint_has_valid_email_addresses()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'contactPoint' => [
                'email' => [
                    'info@publiq.be',
                    'foo@publiq.be',
                ],
            ],
        ];

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_pass_if_contactPoint_has_valid_urls()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'contactPoint' => [
                'url' => [
                    'https://www.publiq.be',
                    'https://www.uitdatabank.be',
                ],
            ],
        ];

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_pass_if_contactPoint_has_multiple_valid_properties()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'contactPoint' => [
                'phone' => [
                    '02 551 18 70',
                    '02/551 18 70',
                ],
                'email' => [
                    'info@publiq.be',
                    'foo@publiq.be',
                ],
                'url' => [
                    'https://www.publiq.be',
                    'https://www.uitdatabank.be',
                ],
            ],
        ];

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_priceInfo_has_no_base_price()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'priceInfo' => [
                [
                    'category' => 'tariff',
                    'name' => [
                        'nl' => 'Senioren',
                    ],
                    'price' => 5.50,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => [
                        'nl' => 'Studenten',
                    ],
                    'price' => 7.50,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $expectedErrors = [
            'priceInfo must contain exactly 1 base price',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_priceInfo_has_more_than_one_base_price()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Senioren',
                    ],
                    'price' => 5.50,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $expectedErrors = [
            'priceInfo must contain exactly 1 base price',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_priceInfo_has_an_invalid_tariff()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => 'Senioren',
                    'price' => '100',
                    'priceCurrency' => 'USD',
                ],
            ],
        ];

        $expectedErrors = [
            'These rules must pass for each priceInfo entry',
            'tariff name must be of the type array',
            'At least one of these rules must pass for price',
            'price must be of the type integer',
            'price must be of the type float',
            'priceCurrency must be equals "EUR"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_priceInfo_has_exactly_one_valid_base_price()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                    ],
                    'price' => 10.0,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $this->validator->assert($event);

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_pass_if_priceInfo_has_exactly_one_base_price_with_price_as_an_integer()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $this->validator->assert($event);

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_pass_if_priceInfo_has_exactly_one_base_price_and_one_or_more_valid_tariffs()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'priceInfo' => [
                [
                    'category' => 'base',
                    'name' => [
                        'nl' => 'Basistarief',
                    ],
                    'price' => 10,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => [
                        'nl' => 'Senioren',
                    ],
                    'price' => 5.50,
                    'priceCurrency' => 'EUR',
                ],
                [
                    'category' => 'tariff',
                    'name' => [
                        'nl' => 'Studenten',
                    ],
                    'price' => 7.50,
                    'priceCurrency' => 'EUR',
                ],
            ],
        ];

        $this->validator->assert($event);

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_multiple_phone_numbers()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'bookingInfo' => [
                'phone' => [
                    '044/444444',
                    '055/555555',
                ],
            ],
        ];

        $expectedErrors = [
            'phone must be a string',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_an_empty_phone_number()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'bookingInfo' => [
                'phone' => '   ',
            ],
        ];

        $expectedErrors = [
            'phone must not be empty',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_multiple_email_addresses()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'bookingInfo' => [
                'email' => [
                    'info@publiq.be',
                    'test@publiq.be',
                ],
            ],
        ];

        $expectedErrors = [
            'email must be valid email',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_an_invalid_email_address()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'bookingInfo' => [
                'email' => 'https://www.publiq.be',
            ],
        ];

        $expectedErrors = [
            'email must be valid email',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_multiple_urls()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'bookingInfo' => [
                'url' => [
                    'http://www.publiq.be',
                    'http://www.uitdatabank.be',
                ],
            ],
        ];

        $expectedErrors = [
            'These rules must pass for bookingInfo',
            'url must be a URL',
            'Key urlLabel must be present',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_an_invalid_url()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'bookingInfo' => [
                'url' => 'info@publiq.be',
            ],
        ];

        $expectedErrors = [
            'These rules must pass for bookingInfo',
            'url must be a URL',
            'Key urlLabel must be present',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_an_invalid_urlLabel()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'bookingInfo' => [
                'url' => 'https://www.publiq.be',
                'urlLabel' => 'Publiq vzw',
            ],
        ];

        $expectedErrors = [
            'urlLabel must be of the type array',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_an_invalid_availabilityStarts_or_availabilityEnds()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'bookingInfo' => [
                'availabilityStarts' => '01/01/2018',
                'availabilityEnds' => '2018-01-02',
            ],
        ];

        $expectedErrors = [
            'These rules must pass for bookingInfo',
            'availabilityStarts must be a valid date. Sample format: "2005-12-30T01:02:03+01:00"',
            'availabilityEnds must be a valid date. Sample format: "2005-12-30T01:02:03+01:00"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_bookingInfo_has_an_availabilityStarts_after_availabilityEnds()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'bookingInfo' => [
                'availabilityStarts' => '2005-12-31T01:02:03+00:00',
                'availabilityEnds' => '2005-12-30T01:02:03+00:00',
            ],
        ];

        $expectedErrors = [
            'availabilityEnds must be greater than or equal to "2005-12-31T01:02:03+00:00"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_bookingInfo_is_empty()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'bookingInfo' => [],
        ];

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_pass_if_bookingInfo_has_valid_properties()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'bookingInfo' => [
                'phone' => '44/444444',
                'email' => 'info@publiq.be',
                'url' => 'https://www.publiq.be',
                'urlLabel' => [
                    'nl' => 'Publiq vzw',
                ],
                'availabilityStarts' => '2005-12-30T01:02:03+00:00',
                'availabilityEnds' => '2005-12-31T01:02:03+00:00',
            ],
        ];

        $this->assertTrue($this->validator->validate($event));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_mediaObject_is_missing_a_required_property()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'mediaObject' => [
                [],
            ],
        ];

        $expectedErrors = [
            'These rules must pass for each mediaObject',
            'Key @id must be present',
            'Key description must be present',
            'Key copyrightHolder must be present',
            'Key inLanguage must be present',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_mediaObject_has_an_invalid_contentUrl_or_thumbnailUrl()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'mediaObject' => [
                [
                    '@id' => 'http://io.uitdatabank.be/media/5cdacc0b-a96b-4613-81e0-1748c179432f',
                    'description' => 'Example description',
                    'copyrightHolder' => 'Example copyright holder',
                    'inLanguage' => 'nl',
                    'contentUrl' => 'info@publiq.be',
                    'thumbnailUrl' => 'info@publiq.be',
                ],
            ],
        ];

        $expectedErrors = [
            'These rules must pass for each mediaObject',
            'contentUrl must be a URL',
            'thumbnailUrl must be a URL',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_if_mediaObject_has_an_invalid_type()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'mediaObject' => [
                [
                    '@id' => 'http://io.uitdatabank.be/media/5cdacc0b-a96b-4613-81e0-1748c179432f',
                    '@type' => 'schema:foo',
                    'description' => 'Example description',
                    'copyrightHolder' => 'Example copyright holder',
                    'inLanguage' => 'nl',
                ],
            ],
        ];

        $expectedErrors = [
            'At least one of these rules must pass for @type',
            '@type must be equal to "schema:ImageObject"',
            '@type must be equal to "schema:mediaObject"',
        ];

        $this->assertValidationErrors($event, $expectedErrors);
    }

    /**
     * @test
     */
    public function it_should_pass_if_mediaObject_has_valid_properties()
    {
        $event = [
            '@id' => 'https://io.uitdatabank.be/events/b19d4090-db47-4520-ac1a-880684357ec9',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'location' => [
                '@id' => 'http://io.uitdatabank.be/place/9a344f43-1174-4149-ad9a-3e2e92565e35',
            ],
            'terms' => [
                [
                    'id' => '0.50.1.0.0',
                ],
            ],
            'mediaObject' => [
                [
                    '@id' => 'http://io.uitdatabank.be/media/5cdacc0b-a96b-4613-81e0-1748c179432f',
                    '@type' => 'schema:ImageObject',
                    'contentUrl' => 'http://io.uitdatabank.be/uploads/5cdacc0b-a96b-4613-81e0-1748c179432f.png',
                    'thumbnailUrl' => 'http://io.uitdatabank.be/uploads/5cdacc0b-a96b-4613-81e0-1748c179432f.png',
                    'description' => 'Example description',
                    'copyrightHolder' => 'Example copyright holder',
                    'inLanguage' => 'nl',
                ],
            ],
        ];

        $this->assertTrue($this->validator->validate($event));
    }


    private function assertValidationErrors($data, array $expectedMessages)
    {
        try {
            $this->getValidator()->assert($data);
            $this->fail('No error messages found.');
        } catch (NestedValidationException $e) {
            $actualMessages = $e->getMessages();

            if (count(array_diff($actualMessages, $expectedMessages)) > 0) {
                var_dump($actualMessages);
            }

            $this->assertEquals($expectedMessages, $actualMessages);
        }
    }

    /**
     * @return Validator
     */
    private function getValidator()
    {
        return $this->validator;
    }
}
