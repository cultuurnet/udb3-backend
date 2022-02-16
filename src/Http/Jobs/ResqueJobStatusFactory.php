<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Jobs;

use Resque_Job_Status;
use CultuurNet\UDB3\StringLiteral;

class ResqueJobStatusFactory implements JobsStatusFactoryInterface
{
    public function createFromJobId(StringLiteral $jobId): ?JobStatus
    {
        $resqueJobStatus = new Resque_Job_Status($jobId->toNative());
        $code = $resqueJobStatus->get();

        if ($code) {
            return new JobStatus(JobStatus::getAllowedValues()[$code - 1]);
        }

        return null;
    }
}
