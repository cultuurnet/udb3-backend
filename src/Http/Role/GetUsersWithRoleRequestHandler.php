<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GetUsersWithRoleRequestHandler implements RequestHandlerInterface
{
    private DocumentRepository $userRolesRepository;

    public function __construct(DocumentRepository $userRolesRepository)
    {
        $this->userRolesRepository = $userRolesRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameter = new RouteParameters($request);
        $roleId = $routeParameter->getRoleId();

        $document = $this->userRolesRepository->fetch($roleId);

        return new JsonResponse(array_values($document->getAssocBody()));
    }
}
