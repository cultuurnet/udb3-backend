<?php

namespace CultuurNet\UDB3\Offer\Security;

use CultuurNet\Search\Parameter\Query;
use ValueObjects\StringLiteral\StringLiteral;

abstract class SearchQueryFactoryBase implements SearchQueryFactoryInterface
{
    /**
     * @inheritdoc
     */
    public function createFromConstraint(
        StringLiteral $constraint,
        StringLiteral $offerId
    ) {
        return new Query($this->createQueryString($constraint, $offerId));
    }

    /**
     * @inheritdoc
     */
    public function createFromConstraints(
        array $constraints,
        StringLiteral $offerId
    ) {
        $queryString = '';

        foreach ($constraints as $constraint) {
            if (strlen($queryString)) {
                $queryString .= ' OR ';
            }

            $queryString .= $this->createQueryString($constraint, $offerId);
        }

        return new Query($queryString);
    }

    /**
     * @param StringLiteral $constraint
     * @param StringLiteral $offerId
     * @return string
     */
    abstract protected function createQueryString(
        StringLiteral $constraint,
        StringLiteral $offerId
    );
}
