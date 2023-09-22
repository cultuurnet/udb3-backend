<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Theme;

interface ThemeResolverInterface
{
    public function byId(string $themeId): Theme;
}
