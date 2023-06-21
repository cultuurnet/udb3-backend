<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey;

use CultureFeed_Uitpas_DistributionKey_Condition;

class DistributionKeyConditionFactory
{
    public function buildCondition(
        string $definition,
        string $operator,
        string $value
    ): CultureFeed_Uitpas_DistributionKey_Condition {
        $condition = new CultureFeed_Uitpas_DistributionKey_Condition();
        $condition->definition = $definition;
        $condition->operator = $operator;
        $condition->value = $value;

        return $condition;
    }
}
