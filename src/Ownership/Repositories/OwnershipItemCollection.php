<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Repositories;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class OwnershipItemCollection extends Collection
{
    public function __construct(OwnershipItem ...$items)
    {
        parent::__construct(...$items);
    }
}
