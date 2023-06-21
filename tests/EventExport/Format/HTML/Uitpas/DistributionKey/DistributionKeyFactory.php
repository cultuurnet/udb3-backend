<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey;

use CultureFeed_Uitpas_DistributionKey_Condition;
use CultureFeed_Uitpas_DistributionKey;

class DistributionKeyFactory
{
    /**
     * @param CultureFeed_Uitpas_DistributionKey_Condition[] $conditions
     */
    public function buildKey(string $tariff, array $conditions): CultureFeed_Uitpas_DistributionKey
    {
        $key = new CultureFeed_Uitpas_DistributionKey();
        $key->tariff = $tariff;
        $key->conditions = $conditions;

        return $key;
    }
}
