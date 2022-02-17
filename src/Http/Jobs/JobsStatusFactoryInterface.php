<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Jobs;

use CultuurNet\UDB3\StringLiteral;

interface JobsStatusFactoryInterface
{
    public function createFromJobId(StringLiteral $jobId): ?JobStatus;
}
