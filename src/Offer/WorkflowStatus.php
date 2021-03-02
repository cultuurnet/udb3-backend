<?php

declare(strict_types=1);

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
    public const READY_FOR_VALIDATION = 'readyforvalidation';
    public const APPROVED = 'approved';
    public const REJECTED = 'rejected';
    public const DRAFT = 'draft';
    public const DELETED = 'deleted';
}
