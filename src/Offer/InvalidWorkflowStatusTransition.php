<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use Exception;

final class InvalidWorkflowStatusTransition extends Exception
{
    public function __construct(WorkflowStatus $from, WorkflowStatus $to)
    {
        parent::__construct(
            'Cannot transition from workflowStatus "' . $from->toString() . '" to "' . $to->toString() . '".'
        );
    }
}
