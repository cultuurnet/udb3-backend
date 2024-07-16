<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
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
        $this->dispatchEventsProjectedToJsonLd(...$production->getEventIds());
    }

    public function addEvent(string $eventId, Production $production): void
    {
        $this->repository->addEvent($eventId, $production);
        $this->dispatchEventsProjectedToJsonLd($eventId, ...$production->getEventIds());
    }

    public function removeEvent(string $eventId, ProductionId $productionId): void
    {
        $this->repository->removeEvent($eventId, $productionId);
        $otherEventIds = $this->getEventIdsForProductionId($productionId);
        $this->dispatchEventsProjectedToJsonLd($eventId, ...$otherEventIds);
    }

    /** @param string[] $eventIds */
    public function removeEvents(array $eventIds, ProductionId $productionId): void
    {
        $this->repository->removeEvents($eventIds, $productionId);
        $otherEventIds = $this->getEventIdsForProductionId($productionId);
        $this->dispatchEventsProjectedToJsonLd(...$eventIds, ...$otherEventIds);
    }

    public function moveEvents(ProductionId $from, Production $to): void
    {
        $this->repository->moveEvents($from, $to);

        // Make sure to fetch the updated list of event ids for the destination production
        $groupedEventIds = $this->getEventIdsForProductionId($to->getProductionId());
        $this->dispatchEventsProjectedToJsonLd(...$groupedEventIds);
    }

    public function renameProduction(ProductionId $productionId, string $name): void
    {
        $this->repository->renameProduction($productionId, $name);

        $groupedEventIds = $this->getEventIdsForProductionId($productionId);
        $this->dispatchEventsProjectedToJsonLd(...$groupedEventIds);
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

    /**
     * @return string[]
     */
    private function getEventIdsForProductionId(ProductionId $productionId): array
    {
        try {
            $production = $this->repository->find($productionId);
            return $production->getEventIds();
        } catch (EntityNotFoundException $e) {
            return [];
        }
    }

    private function dispatchEventsProjectedToJsonLd(string ...$eventIds): void
    {
        $domainMessages = [];
        foreach ($eventIds as $eventId) {
            $eventProjectedToJsonLd = $this->eventFactory->createEvent($eventId);
            $domainMessages[] = (new DomainMessageBuilder())->create($eventProjectedToJsonLd);
        }
        $stream = new DomainEventStream($domainMessages);
        $this->eventBus->publish($stream);
    }
}
