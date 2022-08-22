<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Jobs;

use Resque_Job_Status;

class ResqueJobStatusFactory implements JobsStatusFactoryInterface
{
    public function createFromJobId(string $jobId): ?JobStatus
    {
        $resqueJobStatus = new Resque_Job_Status($jobId);
        $code = $resqueJobStatus->get();

        if ($code) {
            return new JobStatus(JobStatus::getAllowedValues()[$code - 1]);
        }

        return null;
    }
}
