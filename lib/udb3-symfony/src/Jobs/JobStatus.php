<?php

namespace CultuurNet\UDB3\Symfony\Jobs;

use ValueObjects\Enum\Enum;

/**
 * Class JobStatus
 * @package CultuurNet\UDB3\Symfony\Jobs
 * @method static JobStatus WAITING()
 * @method static JobStatus RUNNING()
 * @method static JobStatus FAILED()
 * @method static JobStatus COMPLETE()
 */
class JobStatus extends Enum
{
    const WAITING = 'waiting';
    const RUNNING = 'running';
    const FAILED = 'failed';
    const COMPLETE = 'complete';
}
