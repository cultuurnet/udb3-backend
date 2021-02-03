<?php

namespace CultuurNet\UDB3\Http\Jobs;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ReadRestControllerTest extends TestCase
{
    /**
     * @var JobsStatusFactoryInterface|MockObject
     */
    private $jobsStatusFactory;

    /**
     * @var ReadRestController
     */
    private $readRestController;

    protected function setUp()
    {
        $this->jobsStatusFactory = $this->createMock(
            JobsStatusFactoryInterface::class
        );

        $this->readRestController = new ReadRestController(
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

        $response = $this->readRestController->get('jobId');

        $expectedResponse = new JsonResponse($jobStatus->toNative());

        $this->assertEquals($expectedResponse->getContent(), $response->getContent());
    }

    /**
     * @test
     */
    public function it_returns_a_problem_response_for_missing_job()
    {
        $this->mockCreateFromJobId(null);

        $response = $this->readRestController->get('jobId');

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
