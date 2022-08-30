<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Http\Response\UncacheableJsonResponse;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CurrentUserHasPermissionRequestHandler extends HasPermissionRequestHandler implements RequestHandlerInterface
{
    private ?string $currentUserId;

    public function __construct(
        Permission $permission,
        PermissionVoter $permissionVoter,
        ?string $currentUserId = null
    ) {
        parent::__construct($permission, $permissionVoter);
        $this->currentUserId = $currentUserId;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();

        $hasPermission = $this->hasPermission($offerId, $this->currentUserId);

        return new UncacheableJsonResponse($hasPermission, StatusCodeInterface::STATUS_OK);
    }
}
