<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical\Exception;

class MuseumPassAndUiTPassInSameCluster extends \Exception
{
    public function __construct(string $clusterId, int $amountMuseumpass, int $amountUiTPass)
    {
        parent::__construct(sprintf('Cluster %s contains %d MuseumPass places and %d UiTPAS places', $clusterId, $amountMuseumpass, $amountUiTPass));
    }
}
