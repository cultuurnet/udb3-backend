<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Role\Commands\AddPermission;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Fig\Http\Message\StatusCodeInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class AddPermissionToRoleRequestHandler implements RequestHandlerInterface
{
    private CommandBus $commandBus;

    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $roleId = $routeParameters->getRoleId();
        $permission = $routeParameters->get('permissionKey');

        try {
            $roleId = new UUID($roleId);
        } catch (InvalidArgumentException $e) {
            throw ApiProblem::invalidUUID('roleId');
        }

        try {
            $permission = Permission::fromUpperCaseString($permission);
        } catch (InvalidArgumentException $ex) {
            throw ApiProblem::urlNotFound("Permission $permission is not a valid permission.");
        }

        $this->commandBus->dispatch(new AddPermission($roleId, $permission));

        return new Response(StatusCodeInterface::STATUS_NO_CONTENT);
    }
}
