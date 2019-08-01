<?php

namespace CultuurNet\UDB3\Symfony;

use PHPUnit_Framework_TestCase;
use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Symfony\HttpFoundation\ApiProblemJsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiProblemJsonResponseTest extends PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function has_default_status_HTTP_BAD_REQUEST()
    {
        $apiProblemJsonResponse = new ApiProblemJsonResponse(new ApiProblem());

        $this->assertEquals(
            Response::HTTP_BAD_REQUEST,
            $apiProblemJsonResponse->getStatusCode()
        );
    }

    /**
     * @test
     */
    public function uses_status_from_ApiProblem()
    {
        $apiProblem = new ApiProblem();
        $apiProblem->setStatus(Response::HTTP_NOT_FOUND);

        $apiProblemJsonResponse = new ApiProblemJsonResponse($apiProblem);

        $this->assertEquals(
            Response::HTTP_NOT_FOUND,
            $apiProblemJsonResponse->getStatusCode()
        );
    }
}
