<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\PrivateJsonResponse;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\Security\Permission\UserPermissionChecker;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetPermissionsForGivenUserRequestHandler implements RequestHandlerInterface
{
    private UserPermissionChecker $userPermissionChecker;

    /**
     * @param Permission[] $permissions
     */
    public function __construct(
        array $permissions,
        PermissionVoter $permissionVoter
    ) {
        $this->userPermissionChecker = new UserPermissionChecker($permissions, $permissionVoter);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();
        $userId = $routeParameters->get('userId');

        $permissions = $this->userPermissionChecker->getOwnedPermissions($offerId, $userId);
        return new PrivateJsonResponse(['permissions' => $permissions]);
    }
}
