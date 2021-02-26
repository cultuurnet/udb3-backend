<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Commands;

use CultuurNet\UDB3\Theme;

abstract class AbstractUpdateTheme extends AbstractCommand
{
    /**
     * @var Theme
     */
    protected $theme;

    /**
     * @param string $itemId
     */
    public function __construct($itemId, Theme $theme)
    {
        parent::__construct($itemId);
        $this->theme = $theme;
    }

    /**
     * @return Theme
     */
    public function getTheme()
    {
        return $this->theme;
    }
}
