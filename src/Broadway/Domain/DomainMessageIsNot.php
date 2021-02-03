<?php

namespace CultuurNet\Broadway\Domain;

use Broadway\Domain\DomainMessage;

class DomainMessageIsNot implements DomainMessageSpecificationInterface
{
    /**
     * @var DomainMessageSpecificationInterface
     */
    private $domainMessageSpecification;

    /**
     * @param DomainMessageSpecificationInterface $domainMessageSpecification
     */
    public function __construct(DomainMessageSpecificationInterface $domainMessageSpecification)
    {
        $this->domainMessageSpecification = $domainMessageSpecification;
    }

    /**
     * @param DomainMessage $domainMessage
     * @return bool
     */
    public function isSatisfiedBy(DomainMessage $domainMessage)
    {
        return !$this->domainMessageSpecification->isSatisfiedBy($domainMessage);
    }
}
