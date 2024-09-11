<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Broadway\Domain;

use Broadway\Domain\DomainMessage;

class DomainMessageIsNot implements DomainMessageSpecificationInterface
{
    private DomainMessageSpecificationInterface $domainMessageSpecification;


    public function __construct(DomainMessageSpecificationInterface $domainMessageSpecification)
    {
        $this->domainMessageSpecification = $domainMessageSpecification;
    }

    /**
     * @return bool
     */
    public function isSatisfiedBy(DomainMessage $domainMessage)
    {
        return !$this->domainMessageSpecification->isSatisfiedBy($domainMessage);
    }
}
