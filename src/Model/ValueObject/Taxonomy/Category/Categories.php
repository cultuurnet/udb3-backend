<?php

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category;

use CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour\FiltersDuplicates;
use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

/**
 * @method Category getFirst()
 * @method Category getLast()
 * @method Category getByIndex($index)
 * @method Category[] toArray()
 * @method Categories with(Category $category)
 */
class Categories extends Collection
{
    use FiltersDuplicates;

    /**
     * @param Category[] ...$categories
     */
    public function __construct(Category ...$categories)
    {
        $filtered = $this->filterDuplicateValues($categories);
        parent::__construct(...$filtered);
    }
}
