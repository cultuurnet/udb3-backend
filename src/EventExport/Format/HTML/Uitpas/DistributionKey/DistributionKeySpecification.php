<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey;

interface DistributionKeySpecification
{
    /**
     * @return bool
     */
    public function isSatisfiedBy(\CultureFeed_Uitpas_DistributionKey $distributionKey);
}
