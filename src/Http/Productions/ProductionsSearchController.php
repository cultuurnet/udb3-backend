<?php

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\UDB3\Event\Productions\Production;
use CultuurNet\UDB3\Event\Productions\ProductionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductionsSearchController
{
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
        $keyword = $request->get('keyword');

        return JsonResponse::create(
            array_map(
                function (Production $production) {
                    return $this->transformProduction($production);
                },
                $this->repository->search($keyword)
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
