<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour;

trait HasUniqueValues
{
    /**
     * @throws \InvalidArgumentException
     */
    private function guardUniqueValues(array $values)
    {
        $uniqueValues = array_unique($values, SORT_REGULAR);
        $amountOfDuplicates = count($values) - count($uniqueValues);

        if ($amountOfDuplicates > 0) {
            throw new \InvalidArgumentException("Found {$amountOfDuplicates} duplicates in the given array.");
        }
    }
}
