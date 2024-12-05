<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;

interface TypeResolverInterface
{
    public function byId(string $typeId): Category;
}
