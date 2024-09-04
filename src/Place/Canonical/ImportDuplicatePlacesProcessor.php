<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

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
        $this->deleteOldClusters();
        $this->insertNewClusters();
    }

    private function deleteOldClusters(): void
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

    private function insertNewClusters(): void
    {
        $notYetInClusters = $this->duplicatePlaceRepository->getClustersToImport();

        foreach ($notYetInClusters as $notYetInCluster) {
            $this->duplicatePlaceRepository->addToDuplicatePlaces($notYetInCluster->getClusterId(), $notYetInCluster->getPlaceUuid());
        }
    }
}
