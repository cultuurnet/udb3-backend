<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey;

class KansentariefDiscountSpecification implements DistributionKeySpecification
{
    /**
     * @param \CultureFeed_Uitpas_DistributionKey $distributionKey
     * @return bool
     */
    public function isSatisfiedBy(\CultureFeed_Uitpas_DistributionKey $distributionKey)
    {
        $satisfied = false;
        foreach ($distributionKey->conditions as $condition) {
            if ($condition->definition == $condition::DEFINITION_KANSARM) {
                $satisfied = true;
                break;
            }
        }
        return $satisfied;
    }
}
