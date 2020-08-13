<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Event\ReadModel\DocumentGoneException;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use Doctrine\DBAL\DBALException;

class ProductionCommandHandler extends Udb3CommandHandler
{
    /**
     * @var ProductionRepository
     */
    private $productionRepository;

    /**
     * @var SimilaritiesClient
     */
    private $similaritiesClient;

    /**
     * @var DocumentRepositoryInterface
     */
    private $eventRepository;

    public function __construct(
        ProductionRepository $productionRepository,
        SimilaritiesClient $similaritiesClient,
        DocumentRepositoryInterface $eventRepository
    ) {
        $this->productionRepository = $productionRepository;
        $this->similaritiesClient = $similaritiesClient;
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
            $this->assertEventExists($eventId);
        }

        try {
            $this->productionRepository->add($production);
            $this->eventsWereAddedToProduction($command->getEventIds()[0], $command->getProductionId());
        } catch (DBALException $e) {
            throw EventCannotBeAddedToProduction::becauseSomeEventsBelongToAnotherProduction(
                $command->getEventIds(),
                $command->getProductionId()
            );
        }
    }

    public function handleAddEventToProduction(AddEventToProduction $command): void
    {
        $this->assertEventExists($command->getEventId());

        $production = $this->productionRepository->find($command->getProductionId());
        if ($production->containsEvent($command->getEventId())) {
            return;
        }

        try {
            $this->productionRepository->addEvent($command->getEventId(), $production);
            $this->eventsWereAddedToProduction($command->getEventId(), $command->getProductionId());
        } catch (DBALException $e) {
            throw EventCannotBeAddedToProduction::becauseItAlreadyBelongsToAnotherProduction(
                $command->getEventId(),
                $command->getProductionId()
            );
        }
    }

    public function handleRemoveEventFromProduction(RemoveEventFromProduction $command): void
    {
        $this->productionRepository->removeEvent($command->getEventId(), $command->getProductionId());
    }

    public function handleMergeProductions(MergeProductions $command): void
    {
        $toProduction = $this->productionRepository->find($command->getTo());
        $fromProduction = $this->productionRepository->find($command->getFrom());

        $this->productionRepository->moveEvents($command->getFrom(), $toProduction);

        $this->productionsWereMerged(
            $fromProduction,
            $toProduction
        );
    }

    public function handleRejectSuggestedEventPair(RejectSuggestedEventPair $command): void
    {
        $this->similaritiesClient->excludePermanently([SimilarEventPair::fromArray($command->getEventIds())]);
    }

    private function eventsWereAddedToProduction(string $eventId, ProductionId $productionId): void
    {
        try {
            $pairs = $this->productionRepository->findEventPairs($eventId, $productionId);
            $this->similaritiesClient->excludeTemporarily($pairs);
        } catch (EntityNotFoundException $e) {
        }
    }

    private function productionsWereMerged(
        Production $from,
        Production $to
    ) {
        $eventPairs = [];
        foreach ($from->getEventIds() as $eventIdFrom) {
            foreach ($to->getEventIds() as $eventIdTo) {
                $eventPairs[] = new SimilarEventPair($eventIdFrom, $eventIdTo);
            }
        }
        $this->similaritiesClient->excludeTemporarily($eventPairs);
    }

    private function assertEventExists(string $eventId)
    {
        try {
            $event = $this->eventRepository->get($eventId);
        } catch (DocumentGoneException $e) {
            $event = null;
        }

        if (!$event) {
            throw EventCannotBeAddedToProduction::becauseItDoesNotExist($eventId);
        }
    }
}
