<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Role\Commands\DeleteRole;
use PHPUnit\Framework\TestCase;

final class DeleteRoleRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private DeleteRoleRequestHandler $handler;

    private TraceableCommandBus $commandBus;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();
        $this->handler = new DeleteRoleRequestHandler($this->commandBus);
    }

    /**
     * @test
     */
    public function it_throws_when_role_id_is_invalid(): void
    {
        $roleId = 'not-a-uuid';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->build('DELETE');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::roleNotFound($roleId),
            fn () => $this->handler->handle($request)
        );

        $this->assertEmpty($this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_deletes_a_role(): void
    {
        $roleId = 'd0212d4d-5760-42a6-9b70-838e95ee90df';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->build('DELETE');

        $actualResponse = $this->handler->handle($request);

        $expectedResponse = new NoContentResponse();
        $expectedCommand = new DeleteRole(new Uuid($roleId));

        $this->assertJsonResponse($expectedResponse, $actualResponse);
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }
}
