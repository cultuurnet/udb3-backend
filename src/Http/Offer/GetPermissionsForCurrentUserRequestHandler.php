<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetPermissionsForCurrentUserRequestHandler extends GetPermissionsRequestHandler implements RequestHandlerInterface
{
    private ?string $currentUserId;

    /**
     * @param Permission[] $permissions
     */
    public function __construct(
        array $permissions,
        PermissionVoter $permissionVoter,
        ?string $currentUserId = null
    ) {
        parent::__construct($permissions, $permissionVoter);
        $this->currentUserId = $currentUserId;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (is_null($this->currentUserId)) {
            return new JsonResponse(
                ['permissions' => []],
                StatusCodeInterface::STATUS_OK,
                $this->getPrivateHeaders()
            );
        }

        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();

        $permissions = $this->getPermissions(
            $offerId,
            $this->currentUserId
        );
        return new JsonResponse($permissions, StatusCodeInterface::STATUS_OK, $this->getPrivateHeaders());
    }
}
