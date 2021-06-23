<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\HttpFoundation\Response;

use Crell\ApiProblem\ApiProblem;

final class UnauthorizedResponse extends ApiProblemJsonResponse
{
    public function __construct(string $detail)
    {
        $problem = new ApiProblem('Unauthorized', 'https://api.publiq.be/probs/auth/unauthorized');
        $problem->setStatus(401);
        $problem->setDetail($detail);

        parent::__construct($problem);
    }
}
