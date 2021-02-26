<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Jobs;

use ValueObjects\Enum\Enum;

/**
 * Class JobStatus
 * @package CultuurNet\UDB3\Http\Jobs
 * @method static JobStatus WAITING()
 * @method static JobStatus RUNNING()
 * @method static JobStatus FAILED()
 * @method static JobStatus COMPLETE()
 */
class JobStatus extends Enum
{
    public const WAITING = 'waiting';
    public const RUNNING = 'running';
    public const FAILED = 'failed';
    public const COMPLETE = 'complete';
}
