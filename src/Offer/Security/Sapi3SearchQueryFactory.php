<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Security;

use ValueObjects\StringLiteral\StringLiteral;

final class Sapi3SearchQueryFactory
{
    private function createQueryString(
        StringLiteral $constraint,
        StringLiteral $offerId
    ): string {
        $constraintStr = '(' . $constraint->toNative() . ')';
        $offerIdStr = $offerId->toNative();

        return '(' . $constraintStr . ' AND id:' . $offerIdStr . ')';
    }

    public function createFromConstraints(
        array $constraints,
        StringLiteral $offerId
    ): string {
        $queryString = '';

        foreach ($constraints as $constraint) {
            if (strlen($queryString)) {
                $queryString .= ' OR ';
            }

            $queryString .= $this->createQueryString($constraint, $offerId);
        }

        return $queryString;
    }
}
