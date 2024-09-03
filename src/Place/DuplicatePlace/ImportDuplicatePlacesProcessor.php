<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\DuplicatePlace;

use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRemovedFromClusterRepository;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;
use Doctrine\DBAL\Connection;

class ImportDuplicatePlacesProcessor
{
    private DuplicatePlaceRepository $duplicatePlaceRepository;
    private DuplicatePlaceRemovedFromClusterRepository $duplicatePlacesRemovedFromClusterRepository;
    private Connection $connection;

    public function __construct(
        DuplicatePlaceRepository $duplicatePlaceRepository,
        DuplicatePlaceRemovedFromClusterRepository $duplicatePlacesRemovedFromClusterRepository,
        Connection $connection
    ) {
        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
        $this->duplicatePlacesRemovedFromClusterRepository = $duplicatePlacesRemovedFromClusterRepository;
        $this->connection = $connection;
    }

    public function sync(): void
    {
        $noLongerInClusters = $this->duplicatePlaceRepository->calculateNoLongerInCluster();

        $this->connection->beginTransaction();
        foreach ($noLongerInClusters as $noLongerInCluster) {
            $this->duplicatePlaceRepository->deleteCluster($noLongerInCluster->getClusterId());

            if ($this->duplicatePlaceRepository->countPlacesInDuplicatePlacesImport($noLongerInCluster->getPlaceUuid()->toString()) > 0) {
                // We will only index places that do not occur in any cluster.
                continue;
            }

            $this->duplicatePlacesRemovedFromClusterRepository->addPlace(
                $noLongerInCluster->getPlaceUuid()->toString()
            );
        }
        $this->connection->commit();
    }
}
