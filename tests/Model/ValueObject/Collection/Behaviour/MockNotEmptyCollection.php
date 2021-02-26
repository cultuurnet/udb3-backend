<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MockString;

class MockNotEmptyCollection extends Collection
{
    use IsNotEmpty;

    /**
     * @param MockString[] ...$values
     */
    public function __construct(MockString ...$values)
    {
        $this->guardNotEmpty($values);
        parent::__construct(...$values);
    }
}
