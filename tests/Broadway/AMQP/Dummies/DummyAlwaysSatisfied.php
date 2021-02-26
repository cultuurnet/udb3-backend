<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\Dummies;

use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Broadway\AMQP\DomainMessage\SpecificationInterface;

class DummyAlwaysSatisfied implements SpecificationInterface
{
    public function isSatisfiedBy(DomainMessage $domainMessage)
    {
        return true;
    }
}
