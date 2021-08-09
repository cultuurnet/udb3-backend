<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\HttpFoundation\Response;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response type application/problem+json.
 *
 * @see https://tools.ietf.org/html/draft-nottingham-http-problem-07
 * @deprecated
 *   Use CultuurNet\UDB3\Http\Response\ApiProblemResponse instead where PSR7 can already be used
 **/
class ApiProblemJsonResponse extends JsonResponse
{
    public function __construct(ApiProblem $problem, $headers = [])
    {
        $headers += [
            'Content-Type' => 'application/problem+json',
            'X-Status-Code' => $problem->getStatus(),
        ];
        $status = $problem->getStatus();

        $data = $problem->toArray();

        parent::__construct(
            $data,
            $status,
            $headers
        );
    }
}
