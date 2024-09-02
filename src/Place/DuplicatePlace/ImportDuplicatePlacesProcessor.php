<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\DuplicatePlace;

use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;

/**
 * Calculate with two arrays containing information about places that are no longer in a cluster and those that are not yet in a cluster.
 */
class ImportDuplicatePlacesProcessor
{
    private DuplicatePlaceRepository $duplicatePlaceRepository;

    public function __construct(
        DuplicatePlaceRepository $duplicatePlaceRepository
    ) {
        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
    }

    public function sync(): void
    {
        $noLongerInClusters = $this->duplicatePlaceRepository->calculateNoLongerInCluster();
        $notYetInClusters = $this->duplicatePlaceRepository->calculateNotYetInCluster();

        foreach ($noLongerInClusters as $noLongerInCluster) {
            $this->duplicatePlaceRepository->addToDuplicatePlacesRemovedFromCluster($noLongerInCluster->getClusterId());
        }

        foreach ($notYetInClusters as $notYetInCluster) {
            $this->duplicatePlaceRepository->addToDuplicatePlaces($notYetInCluster->getClusterId(), $notYetInCluster->getPlaceUuid()->toString());
        }
    }
}
