<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;
use CultuurNet\UDB3\Model\ValueObject\String\Behaviour\MockString;

class MockFiltersDuplicatesCollection extends Collection
{
    use FiltersDuplicates;

    /**
     * @param MockString[] ...$values
     */
    public function __construct(MockString ...$values)
    {
        $values = $this->filterDuplicateValues($values);
        parent::__construct(...$values);
    }
}
