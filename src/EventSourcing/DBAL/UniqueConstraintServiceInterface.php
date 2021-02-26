<?php

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Domain\DomainMessage;
use ValueObjects\StringLiteral\StringLiteral;

interface UniqueConstraintServiceInterface
{
    /**
     * @return bool
     */
    public function hasUniqueConstraint(DomainMessage $domainMessage);

    /**
     * @return bool
     */
    public function needsUpdateUniqueConstraint(DomainMessage $domainMessage);

    /**
     * @return StringLiteral
     */
    public function getUniqueConstraintValue(DomainMessage $domainMessage);
}
