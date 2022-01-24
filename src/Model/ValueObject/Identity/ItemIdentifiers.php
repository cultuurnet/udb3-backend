<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Identity;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

final class ItemIdentifiers extends Collection
{
    public function __construct(ItemIdentifier ...$itemIdentifiers)
    {
        parent::__construct(...$itemIdentifiers);
    }
}
