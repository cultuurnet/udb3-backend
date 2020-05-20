<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Theme;
use ValueObjects\StringLiteral\StringLiteral;

interface ThemeResolverInterface
{
    /**
     * @param StringLiteral $themeId
     * @return Theme
     */
    public function byId(StringLiteral $themeId);
}
