<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Canonical;

class MuseumPassNotUniqueInClusterException extends \Exception
{
    public function __construct(int $amount)
    {
        parent::__construct(sprintf('Cluster contains %d MuseumPass places', $amount));
    }
}
