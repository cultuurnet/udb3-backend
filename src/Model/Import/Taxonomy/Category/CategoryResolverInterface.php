<?php

namespace CultuurNet\UDB3\Model\Import\Taxonomy\Category;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;

interface CategoryResolverInterface
{
    /**
     * @param CategoryID $categoryID
     * @return Category|null
     */
    public function byId(CategoryID $categoryID);
}
