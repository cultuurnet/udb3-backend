<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\ConvertsToApiProblem;
use CultuurNet\UDB3\Http\ApiProblem\SchemaError;
use InvalidArgumentException;

class ChildcareTimeInvalid extends InvalidArgumentException implements ConvertsToApiProblem
{
    private int $subEventIndex;
    private string $reason;

    private function __construct(int $subEventIndex, string $reason)
    {
        parent::__construct($reason);
        $this->subEventIndex = $subEventIndex;
        $this->reason = $reason;
    }

    public static function startTimeInvalid(int $subEventIndex): self
    {
        return new self($subEventIndex, 'childcare.start must be before the time portion of startDate');
    }

    public static function endTimeInvalid(int $subEventIndex): self
    {
        return new self($subEventIndex, 'childcare.end must be after the time portion of endDate');
    }

    public function getSubEventIndex(): int
    {
        return $this->subEventIndex;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function toApiProblem(): ApiProblem
    {
        $field = str_contains($this->reason, 'start') ? 'start' : 'end';
        $pointer = '/' . $this->subEventIndex . '/childcare/' . $field;
        return ApiProblem::bodyInvalidData(new SchemaError($pointer, $this->getMessage()));
    }
}
