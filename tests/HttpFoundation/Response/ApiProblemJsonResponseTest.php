<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\HttpFoundation\Response;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class ApiProblemJsonResponseTest extends TestCase
{
    /**
     * @test
     */
    public function it_uses_status_from_the_given_api_problem()
    {
        $apiProblemJsonResponse = new ApiProblemJsonResponse(ApiProblem::internalServerError());

        $this->assertEquals(
            500,
            $apiProblemJsonResponse->getStatusCode()
        );
    }

    /**
     * @test
     */
    public function it_sets_a_problem_json_content_type_header()
    {
        $apiProblemJsonResponse = new ApiProblemJsonResponse(ApiProblem::internalServerError());
        $contentType = $apiProblemJsonResponse->headers->get('Content-Type', '');

        $this->assertEquals('application/problem+json', $contentType);
    }
}
