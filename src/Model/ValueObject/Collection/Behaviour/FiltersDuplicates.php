<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour;

trait FiltersDuplicates
{
    /**
     * @return array
     */
    private function filterDuplicateValues(array $values)
    {
        return array_unique($values, SORT_REGULAR);
    }
}
