<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Place\Canonical\Exception\MuseumPassAndUiTPassInSameCluster;
use CultuurNet\UDB3\Place\Canonical\Exception\MuseumPassNotUniqueInCluster;
use CultuurNet\UDB3\Place\Canonical\Exception\UiTPassNotUniqueInCluster;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

class CanonicalService
{
    private string $museumpasLabel;
    private array $uitpasLabels;

    private DuplicatePlaceRepository $duplicatePlaceRepository;

    private EventRelationsRepository $eventRelationsRepository;

    private ReadRepositoryInterface $labelRelationsRepository;

    private DocumentRepository $placeRepository;

    public function __construct(
        string $museumpasLabel,
        array $uitpasLabels,
        DuplicatePlaceRepository $duplicatePlaceRepository,
        EventRelationsRepository $eventRelationsRepository,
        ReadRepositoryInterface $labelRelationsRepository,
        DocumentRepository $placeRepository
    ) {
        $this->museumpasLabel = $museumpasLabel;
        $this->uitpasLabels = $uitpasLabels;
        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
        $this->eventRelationsRepository = $eventRelationsRepository;
        $this->labelRelationsRepository = $labelRelationsRepository;
        $this->placeRepository = $placeRepository;
    }

    public function getCanonical(string $clusterId): string
    {
        $placeIds = $this->duplicatePlaceRepository->getPlacesInCluster($clusterId);

        $placesWithMuseumpas = $this->getPlacesWithMuseumPasInCluster($placeIds);
        $placesWithUiTPas = $this->getPlacesWithUiTPasInCluster($placeIds);

        if (count($placesWithMuseumpas) > 0 && count($placesWithUiTPas) > 0) {
            throw new MuseumPassAndUiTPassInSameCluster($clusterId, count($placesWithMuseumpas), count($placesWithUiTPas));
        }

        if (count($placesWithMuseumpas) === 1) {
            return $placesWithMuseumpas[array_key_first($placesWithMuseumpas)];
        }
        if (count($placesWithMuseumpas) > 1) {
            throw new MuseumPassNotUniqueInCluster($clusterId, count($placesWithMuseumpas));
        }

        if (count($placesWithUiTPas) === 1) {
            return $placesWithUiTPas[array_key_first($placesWithUiTPas)];
        }
        if (count($placesWithUiTPas) > 1) {
            throw new UiTPassNotUniqueInCluster($clusterId, count($placesWithUiTPas));
        }
        
        $placesWithMostEvents = $this->getPlacesWithMostEvents($placeIds);
        if (count($placesWithMostEvents) === 1) {
            return $placesWithMostEvents[array_key_first($placesWithMostEvents)];
        }

        return $this->getOldestPlace($placeIds);
    }

    private function getPlacesWithMuseumPasInCluster(array $placeIds): array
    {
        $result = $this->labelRelationsRepository->getLabelRelationsForType(
            $this->museumpasLabel,
            RelationType::place()
        );

        return array_intersect($placeIds, $result);
    }

    private function getPlacesWithUiTPasInCluster(array $placeIds): array
    {
        $result = $this->labelRelationsRepository->getLabelRelationsForTypes(
            $this->uitpasLabels,
            RelationType::place()
        );

        return array_intersect($placeIds, $result);
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
