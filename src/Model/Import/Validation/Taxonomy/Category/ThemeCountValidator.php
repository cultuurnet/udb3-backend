<?php

namespace CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;

class ThemeCountValidator extends CategoryCountValidator
{
    public function __construct()
    {
        parent::__construct(
            new CategoryDomain('theme'),
            0,
            1
        );
    }
}
