<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Term;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;

final class TermRepository
{
    private array $mapping;

    public function __construct(array $mapping)
    {
        $this->mapping = $mapping;
    }

    public function getById(string $id): Category
    {
        if (!isset($this->mapping[$id])) {
            throw new TermNotFoundException($id);
        }

        $label = $this->mapping[$id]['name']['nl'] ?? null;
        $label = $label ? new CategoryLabel($label) : null;

        return new Category(new CategoryID($id), $label);
    }
}
