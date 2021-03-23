<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

use CultuurNet\UDB3\Event\Productions\Production;
use CultuurNet\UDB3\Event\Productions\DBALProductionRepository;
use CultuurNet\UDB3\Http\Response\PagedCollectionResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductionsSearchController
{
    private const DEFAULT_START = 0;
    private const DEFAULT_LIMIT = 30;

    /**
     * @var DBALProductionRepository
     */
    private $repository;

    public function __construct(DBALProductionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function search(Request $request): Response
    {
        $keyword = $request->get('name', '');
        $start = (int) $request->get('start', self::DEFAULT_START);
        $limit = (int) $request->get('limit', self::DEFAULT_LIMIT);

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
