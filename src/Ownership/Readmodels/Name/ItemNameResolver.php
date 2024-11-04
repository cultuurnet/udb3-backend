<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels\Name;

interface ItemNameResolver
{
    public function resolve(string $itemId): string;
}
