<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\DistributionKey;

use CultureFeed_Uitpas_DistributionKey;

class KansentariefForCurrentCardSystemSpecification implements DistributionKeySpecification
{
    public function isSatisfiedBy(
        CultureFeed_Uitpas_DistributionKey $distributionKey
    ) {
        $satisfied = false;

        foreach ($distributionKey->conditions as $condition) {
            if ($condition->definition == $condition::DEFINITION_KANSARM &&
                $condition->value == $condition::VALUE_MY_CARDSYSTEM
            ) {
                $satisfied = true;
                break;
            }
        }

        return $satisfied;
    }
}
