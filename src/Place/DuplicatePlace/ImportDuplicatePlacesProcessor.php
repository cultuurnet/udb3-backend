<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\DuplicatePlace;

use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRemovedFromClusterRepository;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;

class ImportDuplicatePlacesProcessor
{
    private DuplicatePlaceRepository $duplicatePlaceRepository;
    private DuplicatePlaceRemovedFromClusterRepository $duplicatePlacesRemovedFromClusterRepository;

    public function __construct(
        DuplicatePlaceRepository $duplicatePlaceRepository,
        DuplicatePlaceRemovedFromClusterRepository $duplicatePlacesRemovedFromClusterRepository
    ) {
        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
        $this->duplicatePlacesRemovedFromClusterRepository = $duplicatePlacesRemovedFromClusterRepository;
    }

    public function sync(): void
    {
        $placesNoLongerInCluster = $this->duplicatePlaceRepository->getPlacesNoLongerInCluster();
        $clustersToBeRemoved = $this->duplicatePlaceRepository->getClustersToBeRemoved();

        foreach ($placesNoLongerInCluster as $placeUuid) {
            $this->duplicatePlacesRemovedFromClusterRepository->addPlace(
                $placeUuid
            );
        }

        foreach ($clustersToBeRemoved as $clusterUuid) {
            $this->duplicatePlaceRepository->deleteCluster($clusterUuid);
        }
    }
}
