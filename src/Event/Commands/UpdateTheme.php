<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Commands;

use CultuurNet\UDB3\Offer\Commands\AbstractCommand;

class UpdateTheme extends AbstractCommand
{
    private string $themeId;

    public function __construct(string $itemId, string $themeId)
    {
        parent::__construct($itemId);
        $this->themeId = $themeId;
    }

    public function getThemeId(): string
    {
        return $this->themeId;
    }
}
