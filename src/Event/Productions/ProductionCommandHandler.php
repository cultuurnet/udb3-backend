<?php

namespace CultuurNet\UDB3\Event\Productions;

use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\EntityNotFoundException;
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

    public function __construct(ProductionRepository $productionRepository, SimilaritiesClient $similaritiesClient)
    {
        $this->productionRepository = $productionRepository;
        $this->similaritiesClient = $similaritiesClient;
    }

    public function handleGroupEventsAsProduction(GroupEventsAsProduction $command): void
    {
        $production = new Production(
            $command->getProductionId(),
            $command->getName(),
            $command->getEventIds()
        );
        $this->productionRepository->add($production);
        $this->markAsLinked($command->getEventIds()[0], $command->getProductionId());
    }

    public function handleAddEventToProduction(AddEventToProduction $command): void
    {
        $production = $this->productionRepository->find($command->getProductionId());
        if ($production->containsEvent($command->getEventId())) {
            return;
        }

        try {
            $this->productionRepository->addEvent($command->getEventId(), $production);
            $this->markAsLinked($command->getEventId(), $command->getProductionId());
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
        $this->productionRepository->moveEvents($command->getFrom(), $toProduction);
    }

    public function handleSkipEvents(SkipEvents $command): void
    {
        $this->similaritiesClient->excludePermanently(EventPair::fromArray($command->getEventIds()));
    }

    /** @param string $eventId
     * @param ProductionId $productionId
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @todo: move logic to event/event handler
     */
    private function markAsLinked(string $eventId, ProductionId $productionId): void
    {
        try {
            $pairs = $this->productionRepository->findEventPairs($eventId, $productionId);
            $this->similaritiesClient->excludeTemporarily($pairs);
        } catch (EntityNotFoundException $e) {
        }
    }
}
