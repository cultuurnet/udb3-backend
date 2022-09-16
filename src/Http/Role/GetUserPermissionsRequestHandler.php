<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Role\ReadModel\Permissions\UserPermissionsReadRepositoryInterface;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GetUserPermissionsRequestHandler implements RequestHandlerInterface
{
    private UserPermissionsReadRepositoryInterface $permissionsRepository;

    private string $currentUserId;

    private bool $userIsGodUser;

    public function __construct(
        UserPermissionsReadRepositoryInterface $permissionsRepository,
        string $currentUserId,
        bool $userIsGodUser
    ) {
        $this->permissionsRepository = $permissionsRepository;
        $this->currentUserId = $currentUserId;
        $this->userIsGodUser = $userIsGodUser;
    }


    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->userIsGodUser) {
            $permissions = Permission::getAllPermissions();
        } else {
            $permissions = $this->permissionsRepository->getPermissions($this->currentUserId);
        }

        $permissions = array_map(
            fn (Permission $permission) => $permission->toUpperCaseString(),
            $permissions
        );

        // Always add the obsolete MEDIA_UPLOADEN permission for backward compatibility with clients that maybe expect
        // it in the /user/permissions response
        $permissions[] = 'MEDIA_UPLOADEN';

        return new JsonResponse(
            $permissions,
            StatusCodeInterface::STATUS_OK
        );
    }
}
