<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Theme;
use ValueObjects\StringLiteral\StringLiteral;

interface ThemeResolverInterface
{
    /**
     * @return Theme
     */
    public function byId(StringLiteral $themeId);
}
