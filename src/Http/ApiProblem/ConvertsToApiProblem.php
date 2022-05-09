<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\ApiProblem;

interface ConvertsToApiProblem
{
    public function toApiProblem(): ApiProblem;
}
