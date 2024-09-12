<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Offer\Events\AbstractEvent;
use CultuurNet\UDB3\Theme;

final class ThemeUpdated extends AbstractEvent
{
    protected Theme $theme;

    /**
     * @param string $itemId
     */
    final public function __construct($itemId, Theme $theme)
    {
        parent::__construct($itemId);
        $this->theme = $theme;
    }

    public function getTheme(): Theme
    {
        return $this->theme;
    }

    public function serialize(): array
    {
        return parent::serialize() + [
                'theme' => $this->theme->serialize(),
            ];
    }

    /**
     * @inheritdoc
     */
    public static function deserialize(array $data)
    {
        return new static($data['item_id'], Theme::deserialize($data['theme']));
    }
}
