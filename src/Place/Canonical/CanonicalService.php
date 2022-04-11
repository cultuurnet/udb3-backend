<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

class CanonicalService
{
    private string $museumpasLabel;

    private RepositoryInterface $eventRelationsRepository;

    private ReadRepositoryInterface $labelRelationsRepository;

    private DocumentRepository $documentRepository;

    public function __construct(
        string $museumpasLabel,
        RepositoryInterface $eventRelationsRepository,
        ReadRepositoryInterface $labelRelationsRepository,
        DocumentRepository $documentRepository
    ) {
        $this->museumpasLabel = $museumpasLabel;
        $this->eventRelationsRepository = $eventRelationsRepository;
        $this->labelRelationsRepository = $labelRelationsRepository;
        $this->documentRepository = $documentRepository;
    }

    public function getCanonical(array $placeIds): string
    {
        $placesWithMuseumpas = $this->getPlacesWithMuseumPasInCluster($placeIds);
        if (count($placesWithMuseumpas) === 1) {
            return $placesWithMuseumpas[array_key_first($placesWithMuseumpas)];
        }

        $placesWithMostEvents = $this->getPlacesWithMostEvents($placeIds);
        if (count($placesWithMostEvents) === 1) {
            return $placesWithMostEvents[array_key_first($placesWithMostEvents)];
        }

        return $this->getOldestPlace($placeIds);
    }

    private function getPlacesWithMuseumPasInCluster(array $placeIds): array
    {
        $result = $this->labelRelationsRepository->getLabelRelationsAsString(
            new LabelName($this->museumpasLabel),
            RelationType::place()
        );

        return array_intersect($placeIds, $result);
    }

    private function getPlacesWithMostEvents(array $placeIds): array
    {
        $eventCountsWithPlaceId = [];
        foreach ($placeIds as $placeId) {
            $eventCount = count($this->eventRelationsRepository->getEventsLocatedAtPlace($placeId));
            if (empty($eventCountsWithPlaceId[$eventCount])) {
                $eventCountsWithPlaceId[$eventCount] = [];
            }

            $eventCountsWithPlaceId[$eventCount][] = $placeId;
        }

        krsort($eventCountsWithPlaceId);
        return $eventCountsWithPlaceId[array_key_first($eventCountsWithPlaceId)];
    }

    private function getOldestPlace(array $placeIds): string
    {
        $placesByCreationDate = [];
        foreach ($placeIds as $placeId) {
            $jsonDocument = $this->documentRepository->fetch($placeId);
            $body = $jsonDocument->getBody();
            $placesByCreationDate[$placeId] = $body->created;
        }
        asort($placesByCreationDate);
        return $placesByCreationDate[array_key_first($placesByCreationDate)];
    }
}
