<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Moderation;

use CultuurNet\UDB3\Model\ValueObject\String\Enum;

/**
 * @method static WorkflowStatus READY_FOR_VALIDATION()
 * @method static WorkflowStatus APPROVED()
 * @method static WorkflowStatus REJECTED()
 * @method static WorkflowStatus DRAFT()
 * @method static WorkflowStatus DELETED()
 */
class WorkflowStatus extends Enum
{
    /**
     * @inheritdoc
     */
    public static function getAllowedValues()
    {
        return [
            'READY_FOR_VALIDATION',
            'APPROVED',
            'REJECTED',
            'DRAFT',
            'DELETED',
        ];
    }
}
