<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\NoContentResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\Commands\RemoveUser;
use PHPUnit\Framework\TestCase;

final class RemoveRoleFromUserRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private RemoveRoleFromUserRequestHandler $handler;

    private TraceableCommandBus $commandBus;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();

        $this->handler = new RemoveRoleFromUserRequestHandler($this->commandBus);
    }

    /**
     * @test
     */
    public function it_throws_when_role_id_is_invalid(): void
    {
        $roleId = 'not-a-uuid';
        $userId = '132c7cdd-d771-4c81-bca1-ba9b93b1f42b';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->withRouteParameter('userId', $userId)
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
    public function it_removes_a_role_from_a_user(): void
    {
        $roleId = '94367f36-6fce-4ad1-920f-5ab0d2f908d5';
        $userId = '132c7cdd-d771-4c81-bca1-ba9b93b1f42b';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->withRouteParameter('userId', $userId)
            ->build('DELETE');

        $actualResponse = $this->handler->handle($request);

        $expectedResponse = new NoContentResponse();
        $expectedCommand = new RemoveUser(
            new UUID($roleId),
            $userId
        );

        $this->assertJsonResponse($expectedResponse, $actualResponse);

        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }
}
