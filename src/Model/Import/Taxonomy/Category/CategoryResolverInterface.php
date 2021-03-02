<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Taxonomy\Category;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;

interface CategoryResolverInterface
{
    /**
     * @return Category|null
     */
    public function byId(CategoryID $categoryID);
}
