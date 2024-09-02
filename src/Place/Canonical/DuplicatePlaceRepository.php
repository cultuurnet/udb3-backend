<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

use CultuurNet\UDB3\Place\DuplicatePlace\Dto\ClusterChangeResult;
use CultuurNet\UDB3\Place\DuplicatePlace\Dto\ClusterRecord;

interface DuplicatePlaceRepository
{
    /**
     * @return string[]
     */
    public function getClusterIds(): array;

    /**
     * @return string[]
     */
    public function getPlacesInCluster(string $clusterId): array;

    public function setCanonicalOnCluster(string $clusterId, string $canonical): void;

    public function getCanonicalOfPlace(string $placeId): ?string;

    public function getDuplicatesOfPlace(string $placeId): ?array;

    public function addToDuplicatePlacesRemovedFromCluster(string $clusterId): void;

    /** @return ClusterRecord[] */
    public function calculateNoLongerInCluster(): array;

    /** @return ClusterRecord[] */
    public function calculateNotYetInCluster(): array;

    public function addToDuplicatePlaces(string $clusterId, string $placeUuid, string $canonical=null): void;

    public function calculateHowManyClustersHaveChanged(): ClusterChangeResult;
    public function howManyPlacesAreToBeImported(): int;
}
