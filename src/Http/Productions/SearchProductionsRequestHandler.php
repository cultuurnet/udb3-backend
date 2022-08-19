<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\UDB3\Event\Productions\Production;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use CultuurNet\UDB3\Http\Request\QueryParameters;
use CultuurNet\UDB3\Http\Response\PagedCollectionResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SearchProductionsRequestHandler implements RequestHandlerInterface
{
    private const DEFAULT_START = 0;
    private const DEFAULT_LIMIT = 30;

    private ProductionRepository $repository;

    public function __construct(ProductionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $queryParameters = new QueryParameters($request);

        $keyword = $queryParameters->get('name', '');
        $start = $queryParameters->getAsInt('start', self::DEFAULT_START);
        $limit = $queryParameters->getAsInt('limit', self::DEFAULT_LIMIT);

        $count = $this->repository->count($keyword);

        if ($count === 0 || $start > $count) {
            return new PagedCollectionResponse(
                $limit,
                $count,
                []
            );
        }

        $serializedProductions = array_map(
            function (Production $production) {
                return $this->transformProduction($production);
            },
            $this->repository->search($keyword, $start, $limit)
        );

        return new PagedCollectionResponse(
            $limit,
            $count,
            $serializedProductions
        );
    }

    private function transformProduction(Production $production): array
    {
        return [
            'name' => $production->getName(),
            'production_id' => $production->getProductionId()->toNative(),
            'events' => $production->getEventIds(),
        ];
    }
}
