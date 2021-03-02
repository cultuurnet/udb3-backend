<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours;

use CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour\HasUniqueValues;
use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

class Days extends Collection
{
    use HasUniqueValues;

    /**
     * @param Day[] ...$days
     */
    public function __construct(Day ...$days)
    {
        $this->guardUniqueValues($days);
        parent::__construct(...$days);
    }
}
