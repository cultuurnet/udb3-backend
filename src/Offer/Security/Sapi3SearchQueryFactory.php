<?php

namespace CultuurNet\UDB3\Offer\Security;

use ValueObjects\StringLiteral\StringLiteral;

/**
 * Implementation of the search query factory for SAPI3.
 */
class Sapi3SearchQueryFactory extends SearchQueryFactoryBase
{
    /**
     * @param StringLiteral $constraint
     * @param StringLiteral $offerId
     * @return string
     */
    protected function createQueryString(
        StringLiteral $constraint,
        StringLiteral $offerId
    ) {
        $constraintStr = '(' . $constraint->toNative() . ')';
        $offerIdStr = $offerId->toNative();

        return '(' . $constraintStr . ' AND id:' . $offerIdStr . ')';
    }
}
