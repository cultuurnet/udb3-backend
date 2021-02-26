<?php

namespace CultuurNet\UDB3\Http\Jobs;

use ValueObjects\StringLiteral\StringLiteral;

interface JobsStatusFactoryInterface
{
    /**
     * @return JobStatus|null
     */
    public function createFromJobId(StringLiteral $jobId);
}
