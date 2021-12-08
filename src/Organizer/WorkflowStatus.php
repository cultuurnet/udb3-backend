<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static WorkflowStatus ACTIVE()
 * @method static WorkflowStatus DELETED()
 */
final class WorkflowStatus extends Enum
{
    public static function getAllowedValues(): array
    {
        return [
            'ACTIVE',
            'DELETED',
        ];
    }
}
