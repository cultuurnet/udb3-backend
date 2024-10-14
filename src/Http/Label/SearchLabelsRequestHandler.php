<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Label;

use CultuurNet\UDB3\Http\Label\Query\QueryFactoryInterface;
use CultuurNet\UDB3\Http\Response\PagedCollectionResponse;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SearchLabelsRequestHandler implements RequestHandlerInterface
{
    private ReadRepositoryInterface $labelRepository;

    private QueryFactoryInterface $queryFactory;

    public function __construct(
        ReadRepositoryInterface $readService,
        QueryFactoryInterface $queryFactory
    ) {
        $this->labelRepository = $readService;
        $this->queryFactory = $queryFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $query = $this->queryFactory->createFromRequest($request);

        $totalEntities = $this->labelRepository->searchTotalLabels($query);

        $entities = $totalEntities > 0 ? $this->labelRepository->searchByLevenshtein($query) : [];

        return new PagedCollectionResponse(
            $query->getLimit() ?? 0,
            $totalEntities,
            $entities
        );
    }
}
