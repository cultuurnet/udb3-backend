<?php

namespace CultuurNet\UDB3\Offer\Security;

use CultuurNet\Search\Parameter\Query;
use ValueObjects\StringLiteral\StringLiteral;

interface SearchQueryFactoryInterface
{
    /**
     * @param StringLiteral $constraint
     * @param StringLiteral $offerId
     * @return Query
     */
    public function createFromConstraint(
        StringLiteral $constraint,
        StringLiteral $offerId
    );

    /**
     * @param StringLiteral[] $constraints
     * @param StringLiteral $offerId
     * @return Query
     */
    public function createFromConstraints(
        array $constraints,
        StringLiteral $offerId
    );
}
