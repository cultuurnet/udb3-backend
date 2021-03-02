<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

class Urls extends Collection
{
    /**
     * @param Url[] ...$values
     */
    public function __construct(Url ...$values)
    {
        parent::__construct(...$values);
    }
}
