<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Validator;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;

interface OfferCommandValidator
{
    public function isValid(string $offerId): bool;

    public function getApiProblem(): ApiProblem;
}
