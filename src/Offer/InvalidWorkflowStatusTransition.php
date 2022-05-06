<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use Exception;

final class InvalidWorkflowStatusTransition extends Exception
{
    public function __construct(WorkflowStatus $workflowStatus)
    {
        parent::__construct(
            'Only an offer with workflow status DRAFT can be published, current status is ' . $workflowStatus->toString()
        );
    }
}
