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
use CultuurNet\UDB3\Role\Commands\AddPermission;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use PHPUnit\Framework\TestCase;

final class AddPermissionToRoleRequestHandlerTest extends TestCase
{
    use AssertApiProblemTrait;
    use AssertJsonResponseTrait;

    private AddPermissionToRoleRequestHandler $handler;

    private TraceableCommandBus $commandBus;

    protected function setUp(): void
    {
        $this->commandBus = new TraceableCommandBus();
        $this->commandBus->record();
        $this->handler = new AddPermissionToRoleRequestHandler($this->commandBus);
    }

    /**
     * @test
     */
    public function it_throws_when_the_role_id_is_not_a_uuid(): void
    {
        $roleId = 'not-a-uuid';
        $permission = Permission::filmsAanmaken();

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->withRouteParameter('permissionKey', $permission->toUpperCaseString())
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::roleNotFound($roleId),
            fn () => $this->handler->handle($request)
        );

        $this->assertEmpty($this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_throws_when_permission_is_invalid(): void
    {
        $roleId = 'de7e30c7-df17-4f5a-a64b-b50af5a2fbe3';
        $permission = 'not-a-permission';

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->withRouteParameter('permissionKey', $permission)
            ->build('PUT');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound('Permission not-a-permission is not a valid permission.'),
            fn () => $this->handler->handle($request)
        );

        $this->assertEmpty($this->commandBus->getRecordedCommands());
    }

    /**
     * @test
     */
    public function it_adds_a_permission_to_a_role(): void
    {
        $roleId = 'de7e30c7-df17-4f5a-a64b-b50af5a2fbe3';
        $permission = Permission::filmsAanmaken();

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('roleId', $roleId)
            ->withRouteParameter('permissionKey', $permission->toUpperCaseString())
            ->build('PUT');

        $actualResponse = $this->handler->handle($request);

        $expectedResponse = new NoContentResponse();
        $expectedCommand = new AddPermission(
            new UUID($roleId),
            $permission
        );

        $this->assertJsonResponse($expectedResponse, $actualResponse);
        $this->assertEquals([$expectedCommand], $this->commandBus->getRecordedCommands());
    }
}
