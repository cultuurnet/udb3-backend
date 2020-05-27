<?php

namespace CultuurNet\UDB3\Offer\Security;

use ValueObjects\StringLiteral\StringLiteral;

class Sapi3SearchQueryFactoryTest extends SearchQueryFactoryTestBase
{
    protected function setUp()
    {
        $this->searchQueryFactory = new Sapi3SearchQueryFactory();
    }

    /**
     * @param StringLiteral $constraint
     * @param StringLiteral $offerId
     * @return string
     */
    public function createQueryString(
        StringLiteral $constraint,
        StringLiteral $offerId
    ) {
        $constraintStr = $constraint->toNative();
        $offerIdStr = $offerId->toNative();

        return '((' . $constraintStr . ') AND id:' . $offerIdStr . ')';
    }
}
