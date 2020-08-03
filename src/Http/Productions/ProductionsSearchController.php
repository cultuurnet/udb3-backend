<?php

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\Hydra\PagedCollection;
use CultuurNet\UDB3\Event\Productions\Production;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductionsSearchController
{
    private const DEFAULT_START = 0;
    private const DEFAULT_LIMIT = 30;

    /**
     * @var ProductionRepository
     */
    private $repository;

    public function __construct(ProductionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function search(Request $request): Response
    {
        $keyword = $request->get('name', '');
        $start = $request->get('start', self::DEFAULT_START);
        $limit = $request->get('limit', self::DEFAULT_LIMIT);
        $pageNumber = (int) ($start / $limit);

        $count = $this->repository->count($keyword);

        if ($count === 0 || $start > $count) {
            return new JsonResponse(
                new PagedCollection(
                    $pageNumber,
                    $limit,
                    [],
                    $count
                )
            );
        }

        $serializedProductions = array_map(
            function (Production $production) {
                return $this->transformProduction($production);
            },
            $this->repository->search($keyword, $start, $limit)
        );

        return new JsonResponse(
            new PagedCollection(
                $pageNumber,
                $limit,
                $serializedProductions,
                $count
            )
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
