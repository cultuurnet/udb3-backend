<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class SubEventUpdates extends Collection
{
    public function __construct(SubEventUpdate ...$subEventUpdates)
    {
        parent::__construct(...$subEventUpdates);
    }

    /**
     * @return SubEventUpdate[]
     */
    public function toArray(): array
    {
        return parent::toArray();
    }
}
