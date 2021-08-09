<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Response;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use Slim\Psr7\Headers;
use Slim\Psr7\Interfaces\HeadersInterface;

/**
 * @see https://tools.ietf.org/html/draft-nottingham-http-problem-07
 **/
class ApiProblemJsonResponse extends JsonResponse
{
    public function __construct(ApiProblem $problem, ?HeadersInterface $headers = null)
    {
        if (!($headers instanceof HeadersInterface)) {
            $headers = new Headers();
        }

        $headers->setHeader('Content-Type', 'application/problem+json');

        $status = $problem->getStatus() ?? 400;
        $data = $problem->asArray();

        parent::__construct(
            $data,
            $status,
            $headers
        );
    }
}
