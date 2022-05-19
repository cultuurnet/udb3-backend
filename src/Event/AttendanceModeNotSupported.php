<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\ConvertsToApiProblem;
use Exception;

final class AttendanceModeNotSupported extends Exception implements ConvertsToApiProblem
{
    public function toApiProblem(): ApiProblem
    {
        return ApiProblem::attendanceModeNotSupported($this->getMessage());
    }
}
