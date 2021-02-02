<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Theme;

abstract class AbstractThemeUpdated extends AbstractEvent
{
    /**
     * @var Theme
     */
    protected $theme;

    /**
     * @param string $itemId
     * @param Theme $theme
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
