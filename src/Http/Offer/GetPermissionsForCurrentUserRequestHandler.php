<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Offer;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\PermissionVoter;
use CultuurNet\UDB3\StringLiteral;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Headers;

final class GetPermissionsForCurrentUserRequestHandler implements RequestHandlerInterface
{
    /**
     * @var Permission[]
     */
    private array $permissions;

    private PermissionVoter $permissionVoter;

    private ?string $currentUserId;

    /**
     * @param Permission[] $permissions
     */
    public function __construct(
        array $permissions,
        PermissionVoter $permissionVoter,
        ?string $currentUserId = null
    ) {
        $this->permissions = $permissions;
        $this->permissionVoter = $permissionVoter;
        $this->currentUserId = $currentUserId;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $headers = new Headers();
        $headers->setHeader('Cache-Control', 'private');
        if (is_null($this->currentUserId)) {
            return new JsonResponse(
                ['permissions' => []],
                StatusCodeInterface::STATUS_OK,
                $headers
            );
        }

        $routeParameters = new RouteParameters($request);
        $offerId = $routeParameters->getOfferId();

        $permissions = $this->getPermissions(
            $offerId,
            $this->currentUserId
        );
        return new JsonResponse($permissions,StatusCodeInterface::STATUS_OK, $headers);
    }

    private function getPermissions(string $offerId, string $userId): array
    {
        $permissionsToReturn = [];
        foreach ($this->permissions as $permission) {
            $hasPermission = $this->permissionVoter->isAllowed(
                $permission,
                new StringLiteral($offerId),
                new StringLiteral($userId)
            );

            if ($hasPermission) {
                $permissionsToReturn[] = $permission->toString();
            }
        }

        return ['permissions' => $permissionsToReturn];
    }
}