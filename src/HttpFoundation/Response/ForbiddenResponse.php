<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\HttpFoundation\Response;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblems;

final class ForbiddenResponse extends ApiProblemJsonResponse
{
    public function __construct(string $detail)
    {
        $problem = ApiProblems::forbidden($detail);
        parent::__construct($problem);
    }
}
