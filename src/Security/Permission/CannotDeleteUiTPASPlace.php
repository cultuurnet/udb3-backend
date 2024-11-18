<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\Permission;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\ConvertsToApiProblem;
use Exception;

class CannotDeleteUiTPASPlace extends Exception implements ConvertsToApiProblem
{
    public function toApiProblem(): ApiProblem
    {
        return ApiProblem::cannotDeleteUitpasPlace();
    }
}
