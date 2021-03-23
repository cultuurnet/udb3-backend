<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;

final class BroadcastingProductionRepository implements ProductionRepository
{
    /**
     * @var ProductionRepository
     */
    private $repository;

    /**
     * @var EventBus
     */
    private $eventBus;

    /**
     * @var DocumentEventFactory
     */
    private $eventFactory;

    public function __construct(
        ProductionRepository $repository,
        EventBus $eventBus,
        DocumentEventFactory $eventFactory
    ) {
        $this->repository = $repository;
        $this->eventBus = $eventBus;
        $this->eventFactory = $eventFactory;
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
