<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\HttpFoundation\Response;

use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response type application/problem+json.
 *
 * @see https://tools.ietf.org/html/draft-nottingham-http-problem-07
 **/
class ApiProblemJsonResponse extends JsonResponse
{
    public function __construct(ApiProblem $problem, $headers = [])
    {
        $headers += ['Content-Type' => 'application/problem+json'];

        $status = Response::HTTP_BAD_REQUEST;
        if (null !== $problem->getStatus()) {
            $headers += [
                'X-Status-Code' => $problem->getStatus(),
            ];
            $status = $problem->getStatus();
        }

        $data = $problem->asArray();

        parent::__construct(
            $data,
            $status,
            $headers
        );
    }
}
