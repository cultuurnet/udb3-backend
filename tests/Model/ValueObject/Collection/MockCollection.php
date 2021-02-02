<?php

namespace CultuurNet\UDB3\Model\ValueObject\Collection;

use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MockString;

class MockCollection extends Collection
{
    /**
     * @param MockString[] ...$values
     */
    public function __construct(MockString ...$values)
    {
        parent::__construct(...$values);
    }
}
