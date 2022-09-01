<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\Security\Permission\UserPermissionChecker;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CurrentUserHasPermissionRequestHandler implements RequestHandlerInterface
{
    private UserPermissionChecker $userPermissionChecker;

    private string $currentUserId;

    public function __construct(
        Permission $permission,
        PermissionVoter $permissionVoter,
        string $currentUserId
    ) {
        $this->userPermissionChecker = new UserPermissionChecker([$permission], $permissionVoter);
        $this->currentUserId = $currentUserId;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();

        $hasPermission = $this->userPermissionChecker->hasPermission($offerId, $this->currentUserId);

        return new JsonResponse(['hasPermission' => $hasPermission]);
    }
}
