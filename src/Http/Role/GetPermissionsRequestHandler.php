<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GetPermissionsRequestHandler implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $permissions = Permission::getAllPermissions();

        $list = [];
        foreach ($permissions as $permission) {
            $list[] = $permission->toUpperCaseString();
        }

        return new JsonResponse(
            $list,
            StatusCodeInterface::STATUS_OK
        );
    }
}
