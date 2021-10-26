<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Offer\Commands\AbstractCommand;
use CultuurNet\UDB3\Theme;

class UpdateTheme extends AbstractCommand
{
    private string $themeId;

    /**
     * @param string $itemId
     */
    public function __construct($itemId, Theme $theme)
    {
        parent::__construct($itemId);
        $this->themeId = $theme->getId();
    }

    public function getThemeId(): string
    {
        return $this->themeId;
    }
}
