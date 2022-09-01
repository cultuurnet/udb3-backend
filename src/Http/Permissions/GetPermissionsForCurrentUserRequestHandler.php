<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Permissions;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\Security\Permission\UserPermissionChecker;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

abstract class GetPermissionsForCurrentUserRequestHandler implements RequestHandlerInterface
{
    private UserPermissionChecker $userPermissionChecker;

    private string $currentUserId;

    /**
     * @param Permission[] $permissions
     */
    public function __construct(
        array $permissions,
        PermissionVoter $permissionVoter,
        string $currentUserId
    ) {
        $this->userPermissionChecker = new UserPermissionChecker($permissions, $permissionVoter);
        $this->currentUserId = $currentUserId;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $itemId = $this->getItemId($routeParameters);

        $permissions = $this->userPermissionChecker->getOwnedPermissions($itemId, $this->currentUserId);

        return new JsonResponse(['permissions' => $permissions]);
    }

    abstract public function getItemId(RouteParameters $routeParameters): string;
}
