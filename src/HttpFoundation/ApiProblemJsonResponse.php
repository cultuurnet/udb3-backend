<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\HttpFoundation;

use Crell\ApiProblem\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Response type application/problem+json.
 *
 * @see https://tools.ietf.org/html/draft-nottingham-http-problem-07
 */
class ApiProblemJsonResponse extends JsonResponse
{
    public function __construct(ApiProblem $problem, $status = 200, $headers = array())
    {
        $headers += [
            'Content-Type' => 'application/problem+json',
        ];

        if (null !== $problem->getStatus()) {
            $headers += [
                'X-Status-Code' => $problem->getStatus()
            ];
        }

        $data = $problem->asArray();

        parent::__construct(
            $data,
            $status,
            $headers
        );
    }

}
