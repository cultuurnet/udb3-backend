<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Validation\ValueObject\Moderation;

use CultuurNet\UDB3\Model\Validation\ValueObject\EnumValidator;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;

class WorkflowStatusValidator extends EnumValidator
{
    protected function getAllowedValues()
    {
        return WorkflowStatus::getAllowedValues();
    }
}
