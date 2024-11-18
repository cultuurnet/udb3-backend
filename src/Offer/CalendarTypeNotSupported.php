<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\ConvertsToApiProblem;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use Exception;

final class CalendarTypeNotSupported extends Exception implements ConvertsToApiProblem
{
    public static function forCalendarType(CalendarType $calendarType): self
    {
        return new self(
            'Updating booking availability on calendar type: "' . strtoupper($calendarType->toString()) . '" is not supported.'
            . ' Only single and multiple calendar types can be updated.'
        );
    }

    public function toApiProblem(): ApiProblem
    {
        return ApiProblem::calendarTypeNotSupported($this->getMessage());
    }
}
