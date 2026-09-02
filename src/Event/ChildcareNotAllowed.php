<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\ConvertsToApiProblem;
use InvalidArgumentException;

final class ChildcareNotAllowed extends InvalidArgumentException implements ConvertsToApiProblem
{
    public const MESSAGE = 'childcare is not allowed when the event has term ' . EventTypeResolver::CHILDCARE_TERM_ID;

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }

    public function toApiProblem(): ApiProblem
    {
        return ApiProblem::bodyInvalidDataWithDetail(self::MESSAGE);
    }
}
