<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category;

use CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour\FiltersDuplicates;
use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

/**
 * @method Category|null getFirst()
 * @method Category|null getLast()
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

    public function getEventType(): ?Category
    {
        return $this->filterDomain(CategoryDomain::eventType());
    }

    public function getTheme(): ?Category
    {
        return $this->filterDomain(CategoryDomain::theme());
    }

    private function filterDomain(CategoryDomain $domainFilter): ?Category
    {
        return $this->filter(
            function (Category $term) use ($domainFilter) {
                $domain = $term->getDomain();
                return $domain && $domain->sameAs($domainFilter);
            }
        )->getFirst();
    }
}
