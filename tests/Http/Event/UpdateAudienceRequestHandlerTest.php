<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\CommandBus;
use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Event\IncompatibleAudienceType;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use PHPUnit\Framework\TestCase;

class UpdateAudienceRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private TraceableCommandBus $commandBus;
    private UpdateAudienceRequestHandler $updateAudienceRequestHandler;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->updateAudienceRequestHandler = new UpdateAudienceRequestHandler($this->commandBus);
        $this->commandBus->record();
    }

    /**
     * @test
     */
    public function it_dispatches_an_update_audience_command(): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withJsonBodyFromArray(['audienceType' => 'members'])
            ->build('PUT');

        $expectedCommand = new UpdateAudience(
            'c269632a-a887-4f21-8455-1631c31e4df5',
            AudienceType::members()
        );

        $response = $this->updateAudienceRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_handles_incompatible_exception_as_api_problem(): void
    {
        $eventId = 'c269632a-a887-4f21-8455-1631c31e4df5';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', $eventId)
            ->withJsonBodyFromArray(['audienceType' => 'members'])
            ->build('PUT');

        $commandBus = $this->createMock(CommandBus::class);
        $commandBus->expects($this->once())
            ->method('dispatch')
            ->willThrowException(IncompatibleAudienceType::forEvent($eventId, AudienceType::members()));
        $updateAudienceRequestHandler = new UpdateAudienceRequestHandler($commandBus);

        $this->assertCallableThrowsApiProblem(
            ApiProblem::inCompatibleAudienceType('The audience type "members" can not be set.'),
            fn () => $updateAudienceRequestHandler->handle($request)
        );
    }

    /**
     * @test
     * @dataProvider invalidBodyDataProvider
     */
    public function it_throws_an_api_problem_for_an_invalid_body(string $body, ApiProblem $expectedApiProblem): void
    {
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('eventId', 'c269632a-a887-4f21-8455-1631c31e4df5')
            ->withBodyFromString($body)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            $expectedApiProblem,
            fn () => $this->updateAudienceRequestHandler->handle($request)
        );
    }

    public function invalidBodyDataProvider(): array
    {
        return [
            [
                '',
                ApiProblem::bodyMissing(),
            ],
            [
                '{{}',
                ApiProblem::bodyInvalidSyntax('JSON'),
            ],
            [
                '{}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'The required properties (audienceType) are missing')
                ),
            ],
            [
                '{"audienceType": 1}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/audienceType', 'The data (integer) must match the type: string')
                ),
            ],
            [
                '{"audienceType": "bla"}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/audienceType', 'The data should match one item from enum')
                ),
            ],
        ];
    }
}
