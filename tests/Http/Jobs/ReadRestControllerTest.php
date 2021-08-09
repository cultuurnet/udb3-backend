<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Jobs;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;

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
    public function it_throws_an_api_problem_exception_for_missing_job()
    {
        $this->expectException(ApiProblem::class);
        $this->mockCreateFromJobId(null);
        $response = $this->readRestController->get('jobId');
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
