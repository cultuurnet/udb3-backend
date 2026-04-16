<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateTypicalBirthDate;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use DateTimeImmutable;
use Iterator;
use PHPUnit\Framework\TestCase;

final class UpdateTypicalBirthDateRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;
    use AssertApiProblemTrait;

    private const EVENT_ID = 'd2a039e9-f4d6-4080-ae33-a106b5d3d47b';

    private TraceableCommandBus $commandBus;

    private UpdateTypicalBirthDateRequestHandler $handler;

    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->handler = new UpdateTypicalBirthDateRequestHandler($this->commandBus);
        $this->psr7RequestBuilder = new Psr7RequestBuilder();
        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_handles_updating_the_typical_birth_date(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withBodyFromString('{"typicalBirthDate": "2020-03-15"}')
            ->build('PUT');

        $response = $this->handler->handle($request);

        $this->assertEquals(
            [new UpdateTypicalBirthDate(self::EVENT_ID, new DateTimeImmutable('2020-03-15'))],
            $this->commandBus->getRecordedCommands()
        );

        $this->assertJsonResponse(new NoContentResponse(), $response);
    }

    /**
     * @test
     * @dataProvider provideInvalidRequestBodies
     */
    public function it_throws_when_the_request_body_is_invalid(
        string $requestBody,
        ApiProblem $expectedProblem
    ): void {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withBodyFromString($requestBody)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            $expectedProblem,
            fn () => $this->handler->handle($request)
        );
    }

    public function provideInvalidRequestBodies(): Iterator
    {
        yield 'missing typicalBirthDate' => [
            'requestBody' => '{}',
            'expectedProblem' => ApiProblem::bodyInvalidData(
                new SchemaError('/typicalBirthDate', 'A valid date string (Y-m-d) is required.')
            ),
        ];

        yield 'invalid date format' => [
            'requestBody' => '{"typicalBirthDate": "15-03-2020"}',
            'expectedProblem' => ApiProblem::bodyInvalidData(
                new SchemaError('/typicalBirthDate', 'The value must be a valid date in the format Y-m-d.')
            ),
        ];

        yield 'invalid date value' => [
            'requestBody' => '{"typicalBirthDate": "2020-13-45"}',
            'expectedProblem' => ApiProblem::bodyInvalidData(
                new SchemaError('/typicalBirthDate', 'The value must be a valid date in the format Y-m-d.')
            ),
        ];

        yield 'not a string' => [
            'requestBody' => '{"typicalBirthDate": 12345}',
            'expectedProblem' => ApiProblem::bodyInvalidData(
                new SchemaError('/typicalBirthDate', 'A valid date string (Y-m-d) is required.')
            ),
        ];
    }
}
