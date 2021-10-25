<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Import\Taxonomy\Category;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;

interface CategoryResolverInterface
{
    public function byId(CategoryID $categoryID): ?Category;
    public function byIdInDomain(CategoryID $categoryID, CategoryDomain $domain): ?Category;
}
