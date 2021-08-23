<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\Status\UpdateStatus;
use PHPUnit\Framework\TestCase;

class UpdateStatusRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;
    private UpdateStatusRequestHandler $requestHandler;

    private const OFFER_ID = '609a8214-51c9-48c0-903f-840a4f38852f';

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->requestHandler = new UpdateStatusRequestHandler($this->commandBus);

        $this->commandBus->record();
    }

    /**
     * @test
     * @dataProvider validDataProvider
     */
    public function it_does_not_throw_when_given_valid_data($data, UpdateStatus $expectedCommand): void
    {
        $this->requestHandler->handle(
            (new Psr7RequestBuilder())
                ->withBodyFromString(json_encode($data))
                ->withRouteParameter('offerId', self::OFFER_ID)
                ->build('PUT')
        );
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    public function validDataProvider(): array
    {
        return [
            'type_available' => [
                'data' => (object) [
                    'type' => 'Available',
                ],
                'expected_command' => new UpdateStatus(
                    self::OFFER_ID,
                    new Status(StatusType::available(), [])
                ),
            ],
            'type_unavailable' => [
                'data' => (object) [
                    'type' => 'Unavailable',
                ],
                'expected_command' => new UpdateStatus(
                    self::OFFER_ID,
                    new Status(StatusType::unavailable(), [])
                ),
            ],
            'type_temporarily_unavailable' => [
                'data' => (object) [
                    'type' => 'TemporarilyUnavailable',
                ],
                'expected_command' => new UpdateStatus(
                    self::OFFER_ID,
                    new Status(StatusType::temporarilyUnavailable(), [])
                ),
            ],
            'type_available_with_reason' => [
                'data' => (object) [
                    'type' => 'Available',
                    'reason' => [
                        'nl' => 'Corona',
                        'en' => 'Covid',
                    ],
                ],
                'expected_command' => new UpdateStatus(
                    self::OFFER_ID,
                    new Status(
                        StatusType::available(),
                        [
                            new StatusReason(new Language('nl'), 'Corona'),
                            new StatusReason(new Language('en'), 'Covid'),
                        ]
                    )
                ),
            ],
            'type_unavailable_with_reason' => [
                'data' => (object) [
                    'type' => 'Unavailable',
                    'reason' => [
                        'nl' => 'Corona',
                        'en' => 'Covid',
                    ],
                ],
                'expected_command' => new UpdateStatus(
                    self::OFFER_ID,
                    new Status(
                        StatusType::unavailable(),
                        [
                            new StatusReason(new Language('nl'), 'Corona'),
                            new StatusReason(new Language('en'), 'Covid'),
                        ]
                    )
                ),
            ],
            'type_temporarily_unavailable_with_reason' => [
                'data' => (object) [
                    'type' => 'TemporarilyUnavailable',
                    'reason' => [
                        'nl' => 'Corona',
                        'en' => 'Covid',
                    ],
                ],
                'expected_command' => new UpdateStatus(
                    self::OFFER_ID,
                    new Status(
                        StatusType::temporarilyUnavailable(),
                        [
                            new StatusReason(new Language('nl'), 'Corona'),
                            new StatusReason(new Language('en'), 'Covid'),
                        ]
                    )
                ),
            ],
            'unknown_language_code' => [
                'data' => (object) [
                    'type' => 'Unavailable',
                    'reason' => [
                        'nl' => 'Corona',
                        'en' => 'Covid',
                        'foo' => 'bar',
                    ],
                ],
                'expected_command' => new UpdateStatus(
                    self::OFFER_ID,
                    new Status(
                        StatusType::unavailable(),
                        [
                            new StatusReason(new Language('nl'), 'Corona'),
                            new StatusReason(new Language('en'), 'Covid'),
                        ]
                    )
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
            fn () => $this->requestHandler->handle(
                (new Psr7RequestBuilder())
                    ->withBodyFromString(json_encode($data))
                    ->withRouteParameter('offerId', self::OFFER_ID)
                    ->build('PUT')
            )
        );

        $this->assertEquals([], $this->commandBus->getRecordedCommands());
    }

    public function invalidDataProvider(): array
    {
        return [
            'not_an_object' => [
                'data' => [],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'The data (array) must match the type: object'),
                ],
            ],
            'missing_type' => [
                'data' => (object) [],
                'expectedSchemaErrors' => [
                    new SchemaError('/', 'The required properties (type) are missing'),
                ],
            ],
            'invalid_type' => [
                'data' => (object) [
                    'type' => 'foo'
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/type', 'The data should match one item from enum'),
                ],
            ],
            'invalid_reason_type' => [
                'data' => (object) [
                    'type' => 'Available',
                    'reason' => 'foo',
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/reason', 'The data (string) must match the type: object'),
                ],
            ],
            'invalid_reason_value_type' => [
                'data' => (object) [
                    'type' => 'Available',
                    'reason' => (object) ['nl' => 123],
                ],
                'expectedSchemaErrors' => [
                    new SchemaError('/reason/nl', 'The data (integer) must match the type: string'),
                ],
            ],
        ];
    }
}
