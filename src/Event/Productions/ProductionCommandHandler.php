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
        $this->grayList($command->getEventIds()[0], $command->getProductionId());
    }

    public function handleAddEventToProduction(AddEventToProduction $command): void
    {
        $production = $this->productionRepository->find($command->getProductionId());
        if ($production->containsEvent($command->getEventId())) {
            return;
        }

        try {
            $this->productionRepository->addEvent($command->getEventId(), $production);
            $this->grayList($command->getEventId(), $command->getProductionId());
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

    /** @param string $eventId
     * @param ProductionId $productionId
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @todo: move logic to event/event handler
     */
    private function grayList(string $eventId, ProductionId $productionId): void
    {
        try {
            $tuples = $this->productionRepository->findTuples($eventId, $productionId);
            $this->similaritiesClient->grayList($tuples);
        } catch (EntityNotFoundException $e) {
        }
    }
}
