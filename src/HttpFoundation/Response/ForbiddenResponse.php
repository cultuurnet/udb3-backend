<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\HttpFoundation\Response;

use Crell\ApiProblem\ApiProblem;

final class ForbiddenResponse extends ApiProblemJsonResponse
{
    public function __construct(string $detail)
    {
        $problem = new ApiProblem('Forbidden', 'https://api.publiq.be/probs/auth/forbidden');
        $problem->setStatus(403);
        $problem->setDetail($detail);

        parent::__construct($problem);
    }
}
