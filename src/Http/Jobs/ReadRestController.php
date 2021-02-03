<?php

namespace CultuurNet\UDB3\Http\Jobs;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\HttpFoundation\Response\ApiProblemJsonResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use ValueObjects\StringLiteral\StringLiteral;

class ReadRestController
{
    /**
     * @var JobsStatusFactoryInterface
     */
    private $jobStatusFactory;

    /**
     * ReadRestController constructor.
     * @param JobsStatusFactoryInterface $jobStatusFactory
     */
    public function __construct(JobsStatusFactoryInterface $jobStatusFactory)
    {
        $this->jobStatusFactory = $jobStatusFactory;
    }

    public function get(string $jobId): JsonResponse
    {
        $jobStatus = $this->jobStatusFactory->createFromJobId(
            new StringLiteral($jobId)
        );

        if ($jobStatus) {
            return new JsonResponse($jobStatus->toNative());
        } else {
            $apiProblem = new ApiProblem('No status for job with id: ' . $jobId);
            $apiProblem->setStatus(Response::HTTP_BAD_REQUEST);

            return new ApiProblemJsonResponse($apiProblem);
        }
    }
}
