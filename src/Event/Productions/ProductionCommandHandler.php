<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Doctrine\DBAL\DBALException;

class ProductionCommandHandler extends Udb3CommandHandler
{
    private ProductionRepository $productionRepository;

    private SkippedSimilarEventsRepository $skippedSimilarEventsRepository;

    private DocumentRepository $eventRepository;

    public function __construct(
        ProductionRepository $productionRepository,
        SkippedSimilarEventsRepository $skippedSimilarEventsRepository,
        DocumentRepository $eventRepository
    ) {
        $this->productionRepository = $productionRepository;
        $this->skippedSimilarEventsRepository = $skippedSimilarEventsRepository;
        $this->eventRepository = $eventRepository;
    }

    public function handleGroupEventsAsProduction(GroupEventsAsProduction $command): void
    {
        $production = new Production(
            $command->getProductionId(),
            $command->getName(),
            $command->getEventIds()
        );

        foreach ($command->getEventIds() as $eventId) {
            $this->assertEventCanBeAddedToProduction($eventId);
        }

        try {
            $this->productionRepository->add($production);
        } catch (DBALException $e) {
            throw EventCannotBeAddedToProduction::becauseSomeEventsBelongToAnotherProduction(
                $command->getEventIds(),
                $command->getProductionId()
            );
        }
    }

    public function handleAddEventToProduction(AddEventToProduction $command): void
    {
        $this->assertEventCanBeAddedToProduction($command->getEventId());

        $production = $this->productionRepository->find($command->getProductionId());
        if ($production->containsEvent($command->getEventId())) {
            throw EventCannotBeAddedToProduction::becauseItAlreadyBelongsToThatProduction(
                $command->getEventId(),
                $command->getProductionId()
            );
        }

        try {
            $this->productionRepository->addEvent($command->getEventId(), $production);
        } catch (DBALException $e) {
            throw EventCannotBeAddedToProduction::becauseItAlreadyBelongsToAnotherProduction(
                $command->getEventId(),
                $command->getProductionId()
            );
        }
    }

    public function handleRemoveEventFromProduction(RemoveEventFromProduction $command): void
    {
        $this->assertEventCanBeRemovedFromProduction($command->getEventId(), $command->getProductionId());
        $this->productionRepository->removeEvent($command->getEventId(), $command->getProductionId());
    }

    public function handleRemoveEventsFromProduction(RemoveEventsFromProduction $command): void
    {
        $this->assertEventsCanBeRemovedFromProduction($command->getEventIds(), $command->getProductionId());
        $this->productionRepository->removeEvents($command->getEventIds(), $command->getProductionId());
    }

    public function handleMergeProductions(MergeProductions $command): void
    {
        $toProduction = $this->productionRepository->find($command->getTo());

        $this->productionRepository->moveEvents($command->getFrom(), $toProduction);
    }

    public function handleRenameProduction(RenameProduction $renameProduction): void
    {
        $this->productionRepository->renameProduction(
            $renameProduction->getProductionId(),
            $renameProduction->getName()
        );
    }

    public function handleRejectSuggestedEventPair(RejectSuggestedEventPair $command): void
    {
        $this->skippedSimilarEventsRepository->add($command->getEventPair());
    }

    private function assertEventCanBeAddedToProduction(string $eventId): void
    {
        try {
            $this->eventRepository->fetch($eventId);
        } catch (DocumentDoesNotExist $e) {
            throw EventCannotBeAddedToProduction::becauseItDoesNotExist($eventId);
        }
    }

    private function assertEventCanBeRemovedFromProduction(string $eventId, ProductionId $productionId): void
    {
        try {
            $this->eventRepository->fetch($eventId);
            $this->productionRepository->find($productionId);
        } catch (DocumentDoesNotExist $e) {
            throw EventCannotBeRemovedFromProduction::becauseItDoesNotExist($eventId);
        } catch (EntityNotFoundException $e) {
            throw EventCannotBeRemovedFromProduction::becauseProductionDoesNotExist($eventId, $productionId);
        }
    }

    /** @param string[] $eventIds */
    private function assertEventsCanBeRemovedFromProduction(array $eventIds, ProductionId $productionId): void
    {
        foreach ($eventIds as $eventId) {
            $this->assertEventCanBeRemovedFromProduction($eventId, $productionId);
        }
    }
}
