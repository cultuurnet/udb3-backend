<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\ConvertsToApiProblem;
use Exception;

final class EmptyCategoryId extends Exception implements ConvertsToApiProblem
{
    public function toApiProblem(): ApiProblem
    {
        return ApiProblem::bodyInvalidDataWithDetail($this->getMessage());
    }
}
