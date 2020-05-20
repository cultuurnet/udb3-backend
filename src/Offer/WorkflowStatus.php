<?php

namespace CultuurNet\UDB3\Offer;

use ValueObjects\Enum\Enum;

/**
 * Class WorkflowStatus
 * @package CultuurNet\UDB3\Offer
 *
 * @method static WorkflowStatus READY_FOR_VALIDATION()
 * @method static WorkflowStatus APPROVED()
 * @method static WorkflowStatus REJECTED()
 * @method static WorkflowStatus DRAFT()
 * @method static WorkflowStatus DELETED()
 */
class WorkflowStatus extends Enum
{
    const READY_FOR_VALIDATION = 'readyforvalidation';
    const APPROVED = 'approved';
    const REJECTED = 'rejected';
    const DRAFT = 'draft';
    const DELETED = 'deleted';
}
