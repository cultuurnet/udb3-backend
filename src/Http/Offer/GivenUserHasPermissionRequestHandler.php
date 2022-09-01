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

final class GivenUserHasPermissionRequestHandler implements RequestHandlerInterface
{
    private UserPermissionChecker $userPermissionChecker;

    public function __construct(
        Permission $permission,
        PermissionVoter $permissionVoter
    ) {
        $this->userPermissionChecker = new UserPermissionChecker([$permission], $permissionVoter);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();
        $userId = $routeParameters->get('userId');

        $hasPermission = $this->userPermissionChecker->hasPermission($offerId, $userId);

        return new JsonResponse(['hasPermission' => $hasPermission]);
    }
}
