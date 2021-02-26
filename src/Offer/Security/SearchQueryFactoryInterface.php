<?php

namespace CultuurNet\UDB3\Offer\Security;

use CultuurNet\SearchV3\Parameter\Query;
use ValueObjects\StringLiteral\StringLiteral;

interface SearchQueryFactoryInterface
{
    /**
     * @return Query
     */
    public function createFromConstraint(
        StringLiteral $constraint,
        StringLiteral $offerId
    );

    /**
     * @param StringLiteral[] $constraints
     * @return Query
     */
    public function createFromConstraints(
        array $constraints,
        StringLiteral $offerId
    );
}
