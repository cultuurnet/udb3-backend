<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\ConvertsToApiProblem;
use Exception;

final class InvalidUrl extends Exception implements ConvertsToApiProblem
{
    public function toApiProblem(): ApiProblem
    {
        return ApiProblem::bodyInvalidDataWithDetail('The url should match pattern: /\\Ahttp[s]?:\\/\\//');
    }
}
