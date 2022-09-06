<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Permissions;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\Security\Permission\UserPermissionChecker;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class GetPermissionsForGivenUserRequestHandler implements RequestHandlerInterface
{
    private UserPermissionChecker $userPermissionChecker;

    public function __construct(
        PermissionVoter $permissionVoter
    ) {
        $this->userPermissionChecker = new UserPermissionChecker($this->getPermissionsToCheck(), $permissionVoter);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $itemId = $this->getItemId($routeParameters);
        $userId = $routeParameters->get('userId');

        $permissions = $this->userPermissionChecker->getOwnedPermissions($itemId, $userId);
        return new JsonResponse(['permissions' => $permissions]);
    }

    abstract public function getItemId(RouteParameters $routeParameters): string;

    abstract public function getPermissionsToCheck(): array;
}
