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

    private ReadRestController $readRestController;

    protected function setUp(): void
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
    public function it_returns_a_json_response_for_existing_job(): void
    {
        $jobStatus = JobStatus::running();
        $this->mockCreateFromJobId($jobStatus);

        $response = $this->readRestController->get('jobId');

        $expectedResponse = new JsonResponse($jobStatus->toString());

        $this->assertEquals($expectedResponse->getContent(), $response->getContent());
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_exception_for_missing_job(): void
    {
        $this->expectException(ApiProblem::class);
        $this->mockCreateFromJobId();
        $this->readRestController->get('jobId');
    }

    private function mockCreateFromJobId(JobStatus $jobStatus = null): void
    {
        $this->jobsStatusFactory->method('createFromJobId')
            ->willReturn($jobStatus);
    }
}
