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
    public static function getAllowedValues(): array
    {
        return [
            'READY_FOR_VALIDATION',
            'APPROVED',
            'REJECTED',
            'DRAFT',
            'DELETED',
        ];
    }

    /// This method is only needed to import UDB2 items, which use
    /// lowercase values without underscores.
    /// It should only be used in those contexts.
    /// It could be removed after UDB2 imports are switched off.
    ///
    public static function fromCultureFeedWorkflowStatus(string $wfStatus): WorkflowStatus
    {
        return new self(str_replace('READYFORVALIDATION', 'READY_FOR_VALIDATION', strtoupper($wfStatus)));
    }
}
