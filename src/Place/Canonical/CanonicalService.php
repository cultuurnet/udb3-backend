<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\Place\Canonical\Exception\MultipleCanonicalPlacesInCluster;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

class CanonicalService
{
    /**
     * @var string[]
     */
    private array $canonicalLabels;

    private DuplicatePlaceRepository $duplicatePlaceRepository;

    private EventRelationsRepository $eventRelationsRepository;

    private ReadRepositoryInterface $labelRelationsRepository;

    private DocumentRepository $placeRepository;

    public function __construct(
        array $canonicalLabels,
        DuplicatePlaceRepository $duplicatePlaceRepository,
        EventRelationsRepository $eventRelationsRepository,
        ReadRepositoryInterface $labelRelationsRepository,
        DocumentRepository $placeRepository
    ) {
        $this->canonicalLabels = $canonicalLabels;
        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
        $this->eventRelationsRepository = $eventRelationsRepository;
        $this->labelRelationsRepository = $labelRelationsRepository;
        $this->placeRepository = $placeRepository;
    }

    public function getCanonical(string $clusterId): string
    {
        $placeIds = $this->duplicatePlaceRepository->getPlacesInCluster($clusterId);

        $labeledPlaces = $this->getPlacesWithCanonicalLabelInCluster($placeIds);

        if (count($labeledPlaces) === 1) {
            return $labeledPlaces[0];
        }

        if (count($labeledPlaces) > 1) {
            throw new MultipleCanonicalPlacesInCluster($clusterId, count($labeledPlaces));
        }

        $placesWithMostEvents = $this->getPlacesWithMostEvents($placeIds);
        if (count($placesWithMostEvents) === 1) {
            return $placesWithMostEvents[0];
        }

        return $this->getOldestPlace($placeIds);
    }

    /**
     * @param string[] $placeIds
     * This function is only used by LookupDuplicatePlaceWithSapi3 to make sure
     * we always return an id. Therefore, it does not throw exceptions and
     * should probably not be used anywhere else.
     */
    public function getCanonicalFromArrayWithoutThrowing(array $placeIds): string
    {
        $labeledPlaces = $this->getPlacesWithCanonicalLabelInCluster($placeIds);

        if (count($labeledPlaces) === 1) {
            return $labeledPlaces[0];
        }

        if (count($labeledPlaces) > 1) {
            $placesWithMostEvents = $this->getPlacesWithMostEvents($labeledPlaces);
            if (count($placesWithMostEvents) === 1) {
                return $placesWithMostEvents[0];
            }
            return $this->getOldestPlace($labeledPlaces);
        }

        $placesWithMostEvents = $this->getPlacesWithMostEvents($placeIds);
        if (count($placesWithMostEvents) === 1) {
            return $placesWithMostEvents[0];
        }

        return $this->getOldestPlace($placeIds);
    }

    private function getPlacesWithCanonicalLabelInCluster(array $placeIds): array
    {
        $result = $this->labelRelationsRepository->getLabelsRelationsForType(
            $this->canonicalLabels,
            RelationType::place()
        );

        return array_values(array_unique(array_intersect($placeIds, $result)));
    }

    private function getPlacesWithMostEvents(array $placeIds): array
    {
        $maxEventCount = -1;
        $placesWithMaxEvents = [];

        foreach ($placeIds as $placeId) {
            $eventCount = count($this->eventRelationsRepository->getEventsLocatedAtPlace($placeId));

            if ($eventCount > $maxEventCount) {
                $maxEventCount = $eventCount;
                $placesWithMaxEvents = [$placeId];
                continue;
            }

            if ($eventCount === $maxEventCount) {
                $placesWithMaxEvents[] = $placeId;
            }
        }

        return $placesWithMaxEvents;
    }

    private function getOldestPlace(array $placeIds): string
    {
        $oldestPlaceId = '';
        $oldestDate = null;

        foreach ($placeIds as $placeId) {
            $jsonDocument = $this->placeRepository->fetch($placeId);
            $body = $jsonDocument->getBody();
            $creationDate = strtotime($body->created);

            if ($creationDate < $oldestDate || $oldestDate === null) {
                $oldestDate = $creationDate;
                $oldestPlaceId = $placeId;
            }
        }

        return $oldestPlaceId;
    }
}
