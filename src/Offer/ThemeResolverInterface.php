<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;

interface ThemeResolverInterface
{
    public function byId(string $themeId): Category;
}
