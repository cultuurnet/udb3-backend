<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\UncacheableJsonResponse;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\Security\Permission\UserPermissionChecker;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetPermissionsForCurrentUserRequestHandler implements RequestHandlerInterface
{
    private UserPermissionChecker $userPermissionChecker;

    private ?string $currentUserId;

    /**
     * @param Permission[] $permissions
     */
    public function __construct(
        array $permissions,
        PermissionVoter $permissionVoter,
        ?string $currentUserId = null
    ) {
        $this->userPermissionChecker = new UserPermissionChecker($permissions, $permissionVoter);
        $this->currentUserId = $currentUserId;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (is_null($this->currentUserId)) {
            new UncacheableJsonResponse(['permissions' => []]);
        }

        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();

        $permissions = $this->userPermissionChecker->getOwnedPermissions($offerId, $this->currentUserId);

        return new UncacheableJsonResponse(['permissions' => $permissions]);
    }
}
