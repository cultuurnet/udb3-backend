<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Role\ReadModel\Search\RepositoryInterface;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RolesSearchRequestHandler implements RequestHandlerInterface
{
    private RepositoryInterface $roleSearchRepository;

    public function __construct(RepositoryInterface $roleSearchRepository)
    {
        $this->roleSearchRepository = $roleSearchRepository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $routeParameters = new RouteParameters($request);

        $query = $routeParameters->getWithDefault('query', '');
        $itemsPerPage = $routeParameters->getWithDefault('limit', '10');
        $start = $routeParameters->getWithDefault('start', '0');

        $result = $this->roleSearchRepository->search($query, $itemsPerPage, $start);

        $data = (object) [
            'itemsPerPage' => $result->getItemsPerPage(),
            'member' => $result->getMember(),
            'totalItems' => $result->getTotalItems(),
        ];

        return new JsonResponse(
            $data,
            StatusCodeInterface::STATUS_OK
        );
    }
}
