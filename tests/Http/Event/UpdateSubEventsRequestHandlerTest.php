<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateSubEvents;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailability;
use CultuurNet\UDB3\Model\ValueObject\Calendar\BookingAvailabilityType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\Status;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusReason;
use CultuurNet\UDB3\Model\ValueObject\Calendar\StatusType;
use CultuurNet\UDB3\Model\ValueObject\Calendar\SubEventUpdate;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedStatusReason;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class UpdateSubEventsRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private const EVENT_ID = '983c06b8-abe8-4286-978f-ca750e3e911d';

    private TraceableCommandBus $commandBus;
    private UpdateSubEventsRequestHandler $requestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();

        $this->requestHandler = new UpdateSubEventsRequestHandler($this->commandBus);

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider validDataProvider
     */
    public function it_does_not_throw_when_given_valid_data($data, UpdateSubEvents $expectedCommand): void
    {
        $this->requestHandler->handle(
            (new Psr7RequestBuilder())
                ->withBodyFromString(json_encode($data))
                ->withRouteParameter('eventId', self::EVENT_ID)
                ->build('PUT')
        );
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    public function validDataProvider(): array
    {
        return [
            'one_subEvent_with_only_id' => [
                'data' => [
                    (object)[
                        'id' => 1,
                    ],
                ],
                'expected_command' => new UpdateSubEvents(
                    self::EVENT_ID,
                    new SubEventUpdate(1)
                ),
            ],
            'two_subEvents_with_only_id' => [
                'data' => [
                    (object)[
                        'id' => 1,
                    ],
                    (object)[
                        'id' => 2,
                    ],
                ],
                'expected_command' => new UpdateSubEvents(
                    self::EVENT_ID,
                    new SubEventUpdate(1),
                    new SubEventUpdate(2)
                ),
            ],
            'one_subEvent_with_start_date' => [
                'data' => [
                    (object)[
                        'id' => 1,
                        'startDate' => '2020-01-01T10:00:00+00:00',
                    ],
                ],
                'expected_command' => new UpdateSubEvents(
                    self::EVENT_ID,
                    (new SubEventUpdate(1))->withStartDate(new DateTimeImmutable('2020-01-01T10:00:00+00:00'))
                ),
            ],
            'one_subEvent_with_start_date_and_end_date' => [
                'data' => [
                    (object)[
                        'id' => 1,
                        'startDate' => '2020-01-01T10:00:00+00:00',
                        'endDate' => '2020-01-01T12:00:00+00:00',
                    ],
                ],
                'expected_command' => new UpdateSubEvents(
                    self::EVENT_ID,
                    (new SubEventUpdate(1))
                        ->withStartDate(new DateTimeImmutable('2020-01-01T10:00:00+00:00'))
                        ->withEndDate(new DateTimeImmutable('2020-01-01T12:00:00+00:00'))
                ),
            ],
            'two_subEvent_with_start_date_and_end_date' => [
                'data' => [
                    (object)[
                        'id' => 1,
                        'startDate' => '2020-01-01T10:00:00+00:00',
                        'endDate' => '2020-01-01T12:00:00+00:00',
                    ],
                    (object)[
                        'id' => 2,
                        'startDate' => '2020-01-02T10:00:00+00:00',
                        'endDate' => '2020-01-02T12:00:00+00:00',
                    ],
                ],
                'expected_command' => new UpdateSubEvents(
                    self::EVENT_ID,
                    (new SubEventUpdate(1))
                        ->withStartDate(new DateTimeImmutable('2020-01-01T10:00:00+00:00'))
                        ->withEndDate(new DateTimeImmutable('2020-01-01T12:00:00+00:00')),
                    (new SubEventUpdate(2))
                        ->withStartDate(new DateTimeImmutable('2020-01-02T10:00:00+00:00'))
                        ->withEndDate(new DateTimeImmutable('2020-01-02T12:00:00+00:00'))
                ),
            ],
            'one_subEvent_with_id_and_status_type_Unavailable' => [
                'data' => [
                    (object)[
                        'id' => 1,
                        'status' => (object)[
                            'type' => 'Unavailable',
                        ],
                    ],
                ],
                'expected_command' => new UpdateSubEvents(
                    self::EVENT_ID,
                    (new SubEventUpdate(1))
                        ->withStatus(new Status(StatusType::Unavailable()))
                ),
            ],
            'two_subEvents_with_id_and_different_status_types' => [
                'data' => [
                    (object)[
                        'id' => 1,
                        'status' => (object)[
                            'type' => 'Unavailable',
                        ],
                    ],
                    (object)[
                        'id' => 2,
                        'status' => (object)[
                            'type' => 'Available',
                        ],
                    ],
                ],
                'expected_command' => new UpdateSubEvents(
                    self::EVENT_ID,
                    (new SubEventUpdate(1))
                        ->withStatus(new Status(StatusType::Unavailable())),
                    (new SubEventUpdate(2))
                        ->withStatus(new Status(StatusType::Available()))
                ),
            ],
            'one_subEvent_with_id_and_status_type_and_reason' => [
                'data' => [
                    (object)[
                        'id' => 1,
                        'status' => (object)[
                            'type' => 'Unavailable',
                            'reason' => (object)[
                                'nl' => 'Geannuleerd wegens covid',
                                'fr' => 'Franse tekst',
                            ],
                        ],
                    ],
                ],
                'expected_command' => new UpdateSubEvents(
                    self::EVENT_ID,
                    (new SubEventUpdate(1))
                        ->withStatus(
                            new Status(
                                StatusType::Unavailable(),
                                (
                                new TranslatedStatusReason(
                                    new Language('nl'),
                                    new StatusReason('Geannuleerd wegens covid')
                                )
                                )->withTranslation(
                                        new Language('fr'),
                                        new StatusReason('Franse tekst')
                                    )
                            )
                        ),
                ),
            ],
            'one_subEvent_with_id_and_bookingAvailability_type' => [
                'data' => [
                    (object)[
                        'id' => 1,
                        'bookingAvailability' => (object)[
                            'type' => 'Unavailable',
                        ],
                    ],
                ],
                'expected_command' => new UpdateSubEvents(
                    self::EVENT_ID,
                    (new SubEventUpdate(1))
                        ->withBookingAvailability(new BookingAvailability(BookingAvailabilityType::Unavailable())),
                ),
            ],
            'one_subEvent_with_id_and_status_type_and_bookingAvailability_type' => [
                'data' => [
                    (object)[
                        'id' => 1,
                        'status' => (object)[
                            'type' => 'Available',
                        ],
                        'bookingAvailability' => (object)[
                            'type' => 'Unavailable',
                        ],
                    ],
                ],
                'expected_command' => new UpdateSubEvents(
                    self::EVENT_ID,
                    (new SubEventUpdate(1))
                        ->withStatus(new Status(StatusType::Available()))
                        ->withBookingAvailability(new BookingAvailability(BookingAvailabilityType::Unavailable())),
                ),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider invalidDataProvider
     */
    public function it_throws_an_api_problem_when_given_invalid_data($data, array $expectedSchemaErrors): void
    {
        $this->assertCallableThrowsApiProblem(
            ApiProblem::bodyInvalidData(...$expectedSchemaErrors),
            fn() => $this->requestHandler->handle(
                (new Psr7RequestBuilder())
                    ->withBodyFromString(json_encode($data))
                    ->withRouteParameter('eventId', self::EVENT_ID)
                    ->build('PUT')
            )
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    public function invalidDataProvider(): array
    {
        return [
            'one_subEvent_without_id' => [
                'data' => [
                    (object)[],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/0', 'The required properties (id) are missing'),
                ],
            ],
            'two_subEvents_without_id' => [
                'data' => [
                    (object)[],
                    (object)[],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'Array must have unique items'),
                ],
            ],
            'two_subEvents_one_with_id_and_the_other_without' => [
                'data' => [
                    (object)['id' => 1],
                    (object)[],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/1', 'The required properties (id) are missing'),
                ],
            ],
            'one_subEvent_with_wrong_start_date' => [
                'data' => [
                    (object)['id' => 1, 'startDate' => '2020-01-01 10:00:00'],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/0/startDate', 'The data must match the \'date-time\' format'),
                ],
            ],
            'one_subEvent_with_wrong_start_date_and_end_date' => [
                'data' => [
                    (object)['id' => 1, 'startDate' => '2020-01-01 10:00:00', 'endDate' => '2020-01-01 12:00:00'],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/0/startDate', 'The data must match the \'date-time\' format'),
                    new SchemaError('/0/endDate', 'The data must match the \'date-time\' format'),
                ],
            ],
            'two_subEvents_one_without_id_and_the_other_with_invalid_status_data_type' => [
                'data' => [
                    (object)['id' => 1, 'status' => 'Unavailable'],
                    (object)[],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/0/status', 'The data (string) must match the type: object'),
                    new SchemaError('/1', 'The required properties (id) are missing'),
                ],
            ],
            'two_subEvents_one_with_invalid_status_type_and_the_other_valid' => [
                'data' => [
                    (object)['id' => 1, 'status' => ['type' => 'invalid']],
                    (object)['id' => 2],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/0/status/type', 'The data should match one item from enum'),
                ],
            ],
            'two_subEvents_one_with_valid_status_type_and_the_other_with_invalid_bookingAvailability_type' => [
                'data' => [
                    (object)['id' => 1, 'status' => ['type' => 'Available']],
                    (object)['id' => 2, 'bookingAvailability' => ['type' => 'invalid']],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/1/bookingAvailability/type', 'The data should match one item from enum'),
                ],
            ],
            'one_subEvent_with_invalid_status_reason' => [
                'data' => [
                    (object)[
                        'id' => 1,
                        'status' => [
                            'type' => 'Available',
                            'reason' => 'foo',
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/0/status/reason', 'The data (string) must match the type: object'),
                ],
            ],
            'one_subEvent_with_only_status_reason_but_no_type' => [
                'data' => [
                    (object)[
                        'id' => 1,
                        'status' => (object)[
                            'reason' => (object)[
                                'nl' => 'Mijn reden in NL',
                            ],
                        ],
                    ],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/0/status', 'The required properties (type) are missing'),
                ],
            ],
        ];
    }
}
