<?php

namespace CultuurNet\UDB3\Model\Import\Validation\Taxonomy\Category;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;

class EventTypeCountValidator extends CategoryCountValidator
{
    public function __construct()
    {
        parent::__construct(
            new CategoryDomain('eventtype'),
            1,
            1
        );
    }
}
