<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour;

trait IsNotEmpty
{
    /**
     * @throws \InvalidArgumentException
     */
    private function guardNotEmpty(array $values)
    {
        if (empty($values)) {
            throw new \InvalidArgumentException('Array should not be empty.');
        }
    }
}
