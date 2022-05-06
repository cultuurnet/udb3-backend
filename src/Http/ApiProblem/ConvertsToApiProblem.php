<?php

namespace CultuurNet\UDB3\Http\ApiProblem;

interface ConvertsToApiProblem
{
    public function toApiProblem(): ApiProblem;
}
