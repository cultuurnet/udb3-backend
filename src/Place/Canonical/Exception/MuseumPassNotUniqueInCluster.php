<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical\Exception;

class MuseumPassNotUniqueInCluster extends \Exception
{
    public function __construct(int $clusterId, int $amount)
    {
        parent::__construct(sprintf('Cluster %s contains %d MuseumPass places', $clusterId, $amount));
    }
}
