<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\DomainMessage;

use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

class SpecificationCollection extends Collection
{
    public function __construct(SpecificationInterface ...$specifications)
    {
        parent::__construct(...$specifications);
    }
}
