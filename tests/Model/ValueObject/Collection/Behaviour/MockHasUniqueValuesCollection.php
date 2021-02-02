<?php

namespace CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MockString;

class MockHasUniqueValuesCollection extends Collection
{
    use HasUniqueValues;

    /**
     * @param MockString[] ...$values
     */
    public function __construct(MockString ...$values)
    {
        $this->guardUniqueValues($values);
        parent::__construct(...$values);
    }
}
