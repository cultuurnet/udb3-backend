<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Price;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

class Tariffs extends Collection
{
    public function __construct(Tariff ...$tariffs)
    {
        parent::__construct(...$tariffs);
    }

    public function hasDuplicates(): bool
    {
        $tariffNames = [];
        foreach ($this->toArray() as $tariff) {
            $name = $tariff->getName();
            if (in_array($name, $tariffNames, true)) {
                return true;
            }
            $tariffNames[] = $name;
        }
        return false;
    }
}
