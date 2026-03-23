<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical\Exception;

use Exception;

final class MultipleCanonicalPlacesInCluster extends Exception
{
    /**
     * @param string[] $placeIds
     */
    public function __construct(string $clusterId, array $placeIds)
    {
        parent::__construct(sprintf(
            'Cluster %s contains %d places with a canonical label: %s',
            $clusterId,
            count($placeIds),
            implode(', ', $placeIds)
        ));
    }
}
