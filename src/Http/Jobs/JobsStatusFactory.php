<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Jobs;

interface JobsStatusFactory
{
    public function createFromJobId(string $jobId): ?JobStatus;
}
