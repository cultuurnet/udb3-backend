<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Jobs;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\AssertApiProblemTrait;
use CultuurNet\UDB3\Http\Request\Psr7RequestBuilder;
use CultuurNet\UDB3\Http\Response\AssertJsonResponseTrait;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GetJobStatusRequestHandlerTest extends TestCase
{
    use AssertJsonResponseTrait;
    use AssertApiProblemTrait;

    /**
     * @var JobsStatusFactory&MockObject
     */
    private $jobsStatusFactory;

    private GetJobStatusRequestHandler $getJobStatusRequestHandler;

    protected function setUp(): void
    {
        $this->jobsStatusFactory = $this->createMock(JobsStatusFactory::class);

        $this->getJobStatusRequestHandler = new GetJobStatusRequestHandler($this->jobsStatusFactory);
    }

    /**
     * @test
     */
    public function it_returns_a_json_response_for_existing_job(): void
    {
        $jobStatus = JobStatus::running();
        $this->mockCreateFromJobId($jobStatus);

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('jobId', '123')
            ->build('GET');

        $response = $this->getJobStatusRequestHandler->handle($request);

        $expectedResponse = new JsonResponse($jobStatus->toString());

        $this->assertJsonResponse($expectedResponse, $response);
    }

    /**
     * @test
     */
    public function it_throws_an_api_problem_exception_for_missing_job(): void
    {
        $this->mockCreateFromJobId();

        $request = (new Psr7RequestBuilder())
            ->withRouteParameter('jobId', '123')
            ->build('GET');

        $this->assertCallableThrowsApiProblem(
            ApiProblem::urlNotFound('No status for job with id: 123'),
            fn () => $this->getJobStatusRequestHandler->handle($request)
        );
    }

    private function mockCreateFromJobId(JobStatus $jobStatus = null): void
    {
        $this->jobsStatusFactory->method('createFromJobId')
            ->willReturn($jobStatus);
    }
}
