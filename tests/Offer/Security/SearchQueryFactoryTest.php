<?php

namespace CultuurNet\UDB3\Offer\Security;

use ValueObjects\StringLiteral\StringLiteral;

class SearchQueryFactoryTest extends SearchQueryFactoryTestBase
{
    protected function setUp()
    {
        $this->searchQueryFactory = new SearchQueryFactory();
    }

    /**
     * @param StringLiteral $constraint
     * @param StringLiteral $offerId
     * @return string
     */
    protected function createQueryString(
        StringLiteral $constraint,
        StringLiteral $offerId
    ) {
        $constraintStr = strtolower($constraint->toNative());
        $offerIdStr = $offerId->toNative();

        return '((' . $constraintStr . ') AND cdbid:' . $offerIdStr . ')';
    }
}
