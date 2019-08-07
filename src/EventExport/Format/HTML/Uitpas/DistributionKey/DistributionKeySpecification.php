<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey;

interface DistributionKeySpecification
{
    /**
     * @param \CultureFeed_Uitpas_DistributionKey $distributionKey
     * @return bool
     */
    public function isSatisfiedBy(\CultureFeed_Uitpas_DistributionKey $distributionKey);
}
