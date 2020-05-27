<?php

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Offer\ThemeResolverInterface;
use CultuurNet\UDB3\Theme;
use ValueObjects\StringLiteral\StringLiteral;

class PlaceThemeResolver implements ThemeResolverInterface
{
    /**
     * @var Theme[]
     */
    private $themes;

    public function __construct()
    {
        $this->themes = [];
    }

    /**
     * @inheritdoc
     */
    public function byId(StringLiteral $themeId)
    {
        if (!array_key_exists((string) $themeId, $this->themes)) {
            throw new \Exception("Unknown place theme id: " . $themeId);
        }
        return $this->themes[(string) $themeId];
    }
}
