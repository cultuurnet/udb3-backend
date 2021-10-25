<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Integer\Behaviour;

trait IsNatural
{
    /**
     * @param int $value
     */
    public function guardNatural($value): void
    {
        /* @var IsInteger $this */
        $this->guardInteger($value);

        if ($value < 0) {
            throw new \InvalidArgumentException(
                "Given integer should be greater or equal to zero. Got {$value} instead."
            );
        }
    }
}
