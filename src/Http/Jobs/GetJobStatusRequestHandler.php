<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Jobs;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\Request\RouteParameters;
use CultuurNet\UDB3\Http\Response\JsonResponse;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class GetJobStatusRequestHandler implements RequestHandlerInterface
{
    private JobsStatusFactory $jobStatusFactory;

    public function __construct(JobsStatusFactory $jobStatusFactory)
    {
        $this->jobStatusFactory = $jobStatusFactory;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $jobId = (new RouteParameters($request))->get('jobId');

        if ($jobId === Uuid::NIL) {
            $jobStatus = JobStatus::complete();
        } else {
            $jobStatus = $this->jobStatusFactory->createFromJobId($jobId);
        }

        if (!$jobStatus) {
            throw ApiProblem::urlNotFound('No status for job with id: ' . $jobId);
        }

        return new JsonResponse($jobStatus->toString());
    }
}
