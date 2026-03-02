<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Categories;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Offer\ThemeResolverInterface;
use Exception;

final class EventThemeResolver implements ThemeResolverInterface
{
    public function __construct(readonly Categories $themes)
    {
    }

    public function byId(string $themeId): Category
    {
        $category = $this->themes->getById(new CategoryID($themeId));
        if ($category === null) {
            throw new Exception('Unknown event theme id: ' . $themeId);
        }

        return $category;
    }
}
