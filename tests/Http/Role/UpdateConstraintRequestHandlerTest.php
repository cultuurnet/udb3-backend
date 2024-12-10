<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\Commands\UpdateConstraint;
use CultuurNet\UDB3\Role\ValueObjects\Query;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;

class UpdateConstraintRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private UpdateConstraintRequestHandler $handler;

    private TraceableCommandBus $commandBus;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();
        $this->handler = new UpdateConstraintRequestHandler($this->commandBus);
    }

    /**
     * @test
     */
    public function it_throws_when_query_is_not_given(): void
    {
        $roleId = '1ed1588b-a771-44ce-bac0-8f19f09a7d0f';
        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->withJsonBodyFromArray([])
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::requiredFieldMissing('query'),
            fn () => $this->handler->handle($request)
        );

        $this->assertEmpty($this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_updates_a_constraint_of_a_role(): void
    {
        $roleId = '1ed1588b-a771-44ce-bac0-8f19f09a7d0f';
        $query = 'constraint-name';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->withJsonBodyFromArray(['query' => $query])
            ->build('PUT');

        $actualResponse = $this->handler->handle($request);

        $expectedResponse = new Response(StatusCodeInterface::STATUS_NO_CONTENT);

        $this->assertJsonResponse($expectedResponse, $actualResponse);

        $expectedCommand = new UpdateConstraint(
            new UUID($roleId),
            new Query($query)
        );

        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }
}
