<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Jobs;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static JobStatus waiting()
 * @method static JobStatus running()
 * @method static JobStatus failed()
 * @method static JobStatus complete()
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
}
