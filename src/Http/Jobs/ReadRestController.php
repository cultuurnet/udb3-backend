<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Jobs;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use CultuurNet\UDB3\StringLiteral;

class ReadRestController
{
    private JobsStatusFactoryInterface $jobStatusFactory;

    public function __construct(JobsStatusFactoryInterface $jobStatusFactory)
    {
        $this->jobStatusFactory = $jobStatusFactory;
    }

    public function get(string $jobId): JsonResponse
    {
        if ($jobId === Uuid::NIL) {
            $jobStatus = JobStatus::complete();
        } else {
            $jobStatus = $this->jobStatusFactory->createFromJobId(
                new StringLiteral($jobId)
            );
        }

        if (!$jobStatus) {
            throw ApiProblem::blank('No status for job with id: ' . $jobId, 400);
        }

        return new JsonResponse($jobStatus->toString());
    }
}
