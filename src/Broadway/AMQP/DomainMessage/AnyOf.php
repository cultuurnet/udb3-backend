<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\AMQP\DomainMessage;

use Broadway\Domain\DomainMessage;

class AnyOf implements SpecificationInterface
{
    private SpecificationCollection $specifications;

    public function __construct(SpecificationCollection $specifications)
    {
        $this->specifications = $specifications;
    }

    public function isSatisfiedBy(DomainMessage $domainMessage): bool
    {
        foreach ($this->specifications as $specification) {
            if ($specification->isSatisfiedBy($domainMessage)) {
                return true;
            }
        }

        return false;
    }
}
