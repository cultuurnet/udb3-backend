<?php

namespace CultuurNet\UDB3\Broadway\AMQP\DomainMessage;

use Broadway\Domain\DomainMessage;

class AnyOf implements SpecificationInterface
{
    /**
     * @var SpecificationCollection|SpecificationInterface[]
     */
    private $specifications;

    public function __construct(SpecificationCollection $specifications)
    {
        $this->specifications = $specifications;
    }

    /**
     * @inheritdoc
     */
    public function isSatisfiedBy(DomainMessage $domainMessage)
    {
        foreach ($this->specifications as $specification) {
            if ($specification->isSatisfiedBy($domainMessage)) {
                return true;
            }
        }

        return false;
    }
}
