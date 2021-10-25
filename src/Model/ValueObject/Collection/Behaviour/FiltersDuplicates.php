<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour;

trait FiltersDuplicates
{
    private function filterDuplicateValues(array $values): array
    {
        return array_unique($values, SORT_REGULAR);
    }
}
