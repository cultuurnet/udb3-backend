<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface;
use CultuurNet\UDB3\Label\ReadModels\Relations\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\Place\Canonical\Exception\MuseumPassNotUniqueInCluster;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

class CanonicalService
{
    private string $museumpasLabel;

    private DuplicatePlaceRepository $duplicatePlaceRepository;

    private RepositoryInterface $eventRelationsRepository;

    private ReadRepositoryInterface $labelRelationsRepository;

    private DocumentRepository $placeRepository;

    public function __construct(
        string $museumpasLabel,
        DuplicatePlaceRepository $duplicatePlaceRepository,
        RepositoryInterface $eventRelationsRepository,
        ReadRepositoryInterface $labelRelationsRepository,
        DocumentRepository $placeRepository
    ) {
        $this->museumpasLabel = $museumpasLabel;
        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
        $this->eventRelationsRepository = $eventRelationsRepository;
        $this->labelRelationsRepository = $labelRelationsRepository;
        $this->placeRepository = $placeRepository;
    }

    public function getCanonicalSet(int $clusterId): array
    {
        $placeIds = $this->duplicatePlaceRepository->getCluster($clusterId);
        $placesWithMuseumpas = $this->getPlacesWithMuseumPasInCluster($placeIds);
        if (count($placesWithMuseumpas) === 1) {
            $canonicalId = $placesWithMuseumpas[array_key_first($placesWithMuseumpas)];
            return [ $canonicalId => array_values(array_diff($placeIds, [$canonicalId]))];
        }
        if (count($placesWithMuseumpas) > 1) {
            throw new MuseumPassNotUniqueInCluster($clusterId, count($placesWithMuseumpas));
        }

        $placesWithMostEvents = $this->getPlacesWithMostEvents($placeIds);
        if (count($placesWithMostEvents) === 1) {
            $canonicalId = $placesWithMostEvents[array_key_first($placesWithMostEvents)];
            return [ $canonicalId => array_values(array_diff($placeIds, [$canonicalId]))];
        }

        $canonicalId = $this->getOldestPlace($placeIds);
        return [ $canonicalId => array_values(array_diff($placeIds, [$canonicalId]))];
    }

    /**
     * @return int[]
     */
    public function getClusterIds(): array
    {
        return $this->duplicatePlaceRepository->getClusterIds();
    }

    private function getPlacesWithMuseumPasInCluster(array $placeIds): array
    {
        $result = $this->labelRelationsRepository->getLabelRelationsForType(
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
            $jsonDocument = $this->placeRepository->fetch($placeId);
            $body = $jsonDocument->getBody();
            $placesByCreationDate[$placeId] = $body->created;
        }

        asort($placesByCreationDate);
        return array_key_first($placesByCreationDate);
    }
}
