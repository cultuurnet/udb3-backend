<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\DuplicatePlace;

use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;
use Doctrine\DBAL\Connection;

class ImportDuplicatePlacesProcessor
{
    private DuplicatePlaceRepository $duplicatePlaceRepository;
    private Connection $connection;

    public function __construct(
        DuplicatePlaceRepository $duplicatePlaceRepository,
        Connection $connection
    ) {
        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
        $this->connection = $connection;
    }

    public function sync(): void
    {
        $noLongerInClusters = $this->duplicatePlaceRepository->calculateNoLongerInCluster();

        $this->connection->beginTransaction();
        foreach ($noLongerInClusters as $noLongerInCluster) {
            $this->duplicatePlaceRepository->deleteCluster($noLongerInCluster->getClusterId());

            if (count($this->duplicatePlaceRepository->calculatePlaceInDuplicatePlacesImport($noLongerInCluster->getPlaceUuid()->toString())) > 0) {
                // We will only index places that do not occur in any cluster.
                continue;
            }

            $this->duplicatePlaceRepository->addToDuplicatePlacesRemovedFromCluster(
                $noLongerInCluster->getPlaceUuid()->toString()
            );
        }
        $this->connection->commit();
    }
}
