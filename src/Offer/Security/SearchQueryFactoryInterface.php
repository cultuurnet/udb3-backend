<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Security;

use ValueObjects\StringLiteral\StringLiteral;

interface SearchQueryFactoryInterface
{
    public function createFromConstraint(
        StringLiteral $constraint,
        StringLiteral $offerId
    ): string;

    public function createFromConstraints(
        array $constraints,
        StringLiteral $offerId
    ): string;
}
