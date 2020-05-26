<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use MabeEnum\Enum;

/**
 * @method static WorkflowStatus ACTIVE()
 * @method static WorkflowStatus DELETED()
 */
final class WorkflowStatus extends Enum
{
    const ACTIVE = 'active';
    const DELETED = 'deleted';
}
