<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey;

use CultureFeed_Uitpas_DistributionKey_Condition;
use CultureFeed_Uitpas_DistributionKey;

class DistributionKeyFactory
{
    /**
     * @param string $tariff
     * @param CultureFeed_Uitpas_DistributionKey_Condition[] $conditions
     * @return CultureFeed_Uitpas_DistributionKey
     */
    public function buildKey($tariff, $conditions)
    {
        $key = new CultureFeed_Uitpas_DistributionKey();
        $key->tariff = $tariff;
        $key->conditions = $conditions;

        return $key;
    }
}
