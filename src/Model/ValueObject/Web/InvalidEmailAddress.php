<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Web;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\ConvertsToApiProblem;
use InvalidArgumentException;

final class InvalidEmailAddress extends InvalidArgumentException implements ConvertsToApiProblem
{
    public function toApiProblem(): ApiProblem
    {
        return ApiProblem::bodyInvalidDataWithDetail($this->getMessage());
    }
}
