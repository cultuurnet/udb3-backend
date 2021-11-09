<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Recommendations;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class Recommendations extends Collection
{
    public function __construct(Recommendation ...$recommendation)
    {
        parent::__construct(...$recommendation);
    }
}
