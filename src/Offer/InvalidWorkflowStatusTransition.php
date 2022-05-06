<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\ConvertsToApiProblem;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use Exception;

final class InvalidWorkflowStatusTransition extends Exception implements ConvertsToApiProblem
{
    private WorkflowStatus $from;
    private WorkflowStatus $to;

    public function __construct(WorkflowStatus $from, WorkflowStatus $to)
    {
        parent::__construct(
            'Cannot transition from workflowStatus "' . $from->toString() . '" to "' . $to->toString() . '".'
        );
        $this->from = $from;
        $this->to = $to;
    }

    public function toApiProblem(): ApiProblem
    {
        return ApiProblem::invalidWorkflowStatusTransition($this->from->toString(), $this->to->toString());
    }
}
