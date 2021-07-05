<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\HttpFoundation\Response;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblems;

final class UnauthorizedResponse extends ApiProblemJsonResponse
{
    public function __construct(string $detail)
    {
        $problem = ApiProblems::unauthorized($detail);
        parent::__construct($problem);
    }
}
