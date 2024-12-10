<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\Commands\RemoveConstraint;
use Fig\Http\Message\StatusCodeInterface;
use PHPUnit\Framework\TestCase;
use Slim\Psr7\Response;

class DeleteConstraintRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;

    private DeleteConstraintRequestHandler $handler;

    private TraceableCommandBus $commandBus;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();
        $this->handler = new DeleteConstraintRequestHandler($this->commandBus);
    }

    /**
     * @test
     */
    public function it_deletes_the_constraint_of_a_role(): void
    {
        $roleId = '1ed1588b-a771-44ce-bac0-8f19f09a7d0f';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->build('DELETE');

        $actualResponse = $this->handler->handle($request);

        $expectedResponse = new Response(StatusCodeInterface::STATUS_NO_CONTENT);

        $this->assertJsonResponse($expectedResponse, $actualResponse);

        $expectedCommand = new RemoveConstraint(
            new Uuid($roleId)
        );

        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }
}
