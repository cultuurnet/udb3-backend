<?php

namespace CultuurNet\UDB3\Http\HttpFoundation;

use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Response type application/problem+json.
 *
 * @see https://tools.ietf.org/html/draft-nottingham-http-problem-07
 *
 * @deprecated Use \Cultuurnet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse instead.
 * @see https://github.com/cultuurnet/udb3-http-foundation/blob/master/src/Response/ApiProblemJsonResponse.php
 **/
class ApiProblemJsonResponse extends JsonResponse
{
    public function __construct(ApiProblem $problem, $headers = array())
    {
        $headers += [
            'Content-Type' => 'application/problem+json',
        ];

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
