<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Event;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Event\Commands\UpdateChildrenOnly;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use PHPUnit\Framework\TestCase;

final class UpdateChildrenOnlyRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;

    private const EVENT_ID = '609a8214-51c9-48c0-903f-840a4f38852f';

    private TraceableCommandBus $commandBus;
    private UpdateChildrenOnlyRequestHandler $handler;
    private Psr7RequestBuilder $psr7RequestBuilder;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->handler = new UpdateChildrenOnlyRequestHandler($this->commandBus);

        $this->psr7RequestBuilder = new Psr7RequestBuilder();
    }

    /**
     * @test
     */
    public function it_dispatches_update_children_only_with_true(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray(['childrenOnly' => true])
            ->build('PUT');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(NoContentResponse::class, $response);
        $this->assertEquals(
            [new UpdateChildrenOnly(self::EVENT_ID, true)],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     */
    public function it_dispatches_update_children_only_with_false(): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withJsonBodyFromArray(['childrenOnly' => false])
            ->build('PUT');

        $response = $this->handler->handle($request);

        $this->assertInstanceOf(NoContentResponse::class, $response);
        $this->assertEquals(
            [new UpdateChildrenOnly(self::EVENT_ID, false)],
            $this->commandBus->getRecordedCommands()
        );
    }

    /**
     * @test
     * @dataProvider invalidBody
     */
    public function it_throws_an_api_problem_for_an_invalid_body(string $body, ApiProblem $expectedApiProblem): void
    {
        $request = $this->psr7RequestBuilder
            ->withRouteParameter('eventId', self::EVENT_ID)
            ->withBodyFromString($body)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            $expectedApiProblem,
            fn () => $this->handler->handle($request)
        );
    }

    public function invalidBody(): array
    {
        return [
            'missing body' => [
                '',
                ApiProblem::bodyMissing(),
            ],
            'invalid syntax' => [
                '{{}',
                ApiProblem::bodyInvalidSyntax('JSON'),
            ],
            'missing childrenOnly property' => [
                '{}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/', 'The required properties (childrenOnly) are missing')
                ),
            ],
            'wrong type for childrenOnly' => [
                '{"childrenOnly":"yes"}',
                ApiProblem::bodyInvalidData(
                    new SchemaError('/childrenOnly', 'The data (string) must match the type: boolean')
                ),
            ],
        ];
    }
}
