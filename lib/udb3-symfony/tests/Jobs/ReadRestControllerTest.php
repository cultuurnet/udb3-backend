<?php

namespace CultuurNet\UDB3\Symfony\Jobs;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Symfony\HttpFoundation\ApiProblemJsonResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ReadRestControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JobsStatusFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $jobsStatusFactory;

    /**
     * @var ReadRestController
     */
    private $readRestConstroller;

    protected function setUp()
    {
        $this->jobsStatusFactory = $this->createMock(
            JobsStatusFactoryInterface::class
        );

        $this->readRestConstroller = new ReadRestController(
            $this->jobsStatusFactory
        );
    }

    /**
     * @test
     */
    public function it_returns_a_json_response_for_existing_job()
    {
        $jobStatus = JobStatus::RUNNING();
        $this->mockCreateFromJobId($jobStatus);

        $response = $this->readRestConstroller->get('jobId');

        $expectedResponse = new JsonResponse($jobStatus->toNative());

        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * @test
     */
    public function it_returns_a_problem_response_for_missing_job()
    {
        $this->mockCreateFromJobId(null);

        $response = $this->readRestConstroller->get('jobId');

        $apiProblem = new ApiProblem('No status for job with id: jobId');
        $apiProblem->setStatus(Response::HTTP_BAD_REQUEST);
        $expectedResponse = new ApiProblemJsonResponse($apiProblem);

        $this->assertEquals($expectedResponse, $response);
    }

    /**
     * @param JobStatus $jobStatus
     */
    private function mockCreateFromJobId(JobStatus $jobStatus = null)
    {
        $this->jobsStatusFactory->method('createFromJobId')
            ->willReturn($jobStatus);
    }
}
