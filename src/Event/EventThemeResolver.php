<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Offer\ThemeResolverInterface;

final class EventThemeResolver implements ThemeResolverInterface
{
    /**
     * @param Category[] $themes
     */
    public function __construct(readonly array $themes)
    {
    }

    public function byId(string $themeId): Category
    {
        if (!array_key_exists($themeId, $this->themes)) {
            throw new \Exception('Unknown event theme id: ' . $themeId);
        }
        return $this->themes[$themeId];
    }
}
