<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateAudience;
use CultuurNet\UDB3\Event\ValueObjects\Audience;
use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
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
            ->withBodyFromArray(['audienceType' => 'members'])
            ->build('PUT');

        $expectedCommand = new UpdateAudience(
            'c269632a-a887-4f21-8455-1631c31e4df5',
            new Audience(AudienceType::MEMBERS())
        );

        $response = $this->updateAudienceRequestHandler->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
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
