<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Role;

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
        $query = $request->getQueryParams()['query'] ?? '';
        $itemsPerPage = $request->getQueryParams()['limit'] ?? 10;
        $start = $request->getQueryParams()['start'] ?? 0;

        $result = $this->roleSearchRepository->search($query,(int) $itemsPerPage, (int) $start);

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
