<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Jobs;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

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
    public static function getAllowedValues(): array
    {
        return [
            'waiting',
            'running',
            'failed',
            'complete',
        ];
    }

    public static function WAITING(): JobStatus
    {
        return new self('waiting');
    }

    public static function RUNNING(): JobStatus
    {
        return new self('running');
    }

    public static function FAILED(): JobStatus
    {
        return new self('failed');
    }

    public static function COMPLETE(): JobStatus
    {
        return new self('complete');
    }
}
