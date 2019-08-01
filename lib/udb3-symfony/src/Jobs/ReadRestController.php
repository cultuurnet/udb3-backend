<?php

namespace CultuurNet\UDB3\Symfony\Jobs;

use Crell\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Symfony\HttpFoundation\ApiProblemJsonResponse;
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

    /**
     * @param $jobId
     * @return ApiProblemJsonResponse|JsonResponse
     */
    public function get($jobId)
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
