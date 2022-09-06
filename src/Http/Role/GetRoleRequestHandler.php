<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetRoleRequestHandler implements RequestHandlerInterface
{
    private DocumentRepository $roleRepository;

    public function __construct(DocumentRepository $roleRepository)
    {
        $this->roleRepository = $roleRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameter = new RouteParameters($request);
        $roleId = $routeParameter->getRoleId();

        try {
            $role = $this->roleRepository->fetch($roleId);
        } catch (DocumentDoesNotExist $e) {
            throw ApiProblem::roleNotFound($roleId);
        }

        return new JsonResponse($role->getRawBody());
    }
}
