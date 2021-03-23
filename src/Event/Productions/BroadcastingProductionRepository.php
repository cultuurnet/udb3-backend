<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

final class BroadcastingProductionRepository implements ProductionRepository
{
    /**
     * @var ProductionRepository
     */
    private $repository;

    public function __construct(
        ProductionRepository $repository
    ) {
        $this->repository = $repository;
    }

    public function add(Production $production): void
    {
        $this->repository->add($production);
    }

    public function addEvent(string $eventId, Production $production): void
    {
        $this->repository->addEvent($eventId, $production);
    }

    public function removeEvent(string $eventId, ProductionId $productionId): void
    {
        $this->repository->removeEvent($eventId, $productionId);
    }

    public function moveEvents(ProductionId $from, Production $to): void
    {
        $this->repository->moveEvents($from, $to);
    }

    public function find(ProductionId $productionId): Production
    {
        return $this->repository->find($productionId);
    }

    public function findProductionForEventId(string $eventId): Production
    {
        return $this->repository->findProductionForEventId($eventId);
    }

    public function search(string $keyword, int $start, int $limit): array
    {
        return $this->repository->search($keyword, $start, $limit);
    }

    public function count(string $keyword): int
    {
        return $this->repository->count($keyword);
    }
}
