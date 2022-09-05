<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\EntityServiceInterface;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetRoleRequestHandler implements RequestHandlerInterface
{
    private EntityServiceInterface $service;

    public function __construct(EntityServiceInterface $service)
    {
        $this->service = $service;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameter = new RouteParameters($request);
        $roleId = $routeParameter->getRoleId();

        try {
            $role = $this->service->getEntity($roleId);
        } catch (EntityNotFoundException $e) {
            throw ApiProblem::roleNotFound($roleId);
        }

        return new JsonResponse($role);
    }
}
