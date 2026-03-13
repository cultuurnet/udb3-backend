<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use InvalidArgumentException;

class ChildcareTimeInvalid extends InvalidArgumentException
{
    private string $field;
    private int $subEventIndex;

    private function __construct(string $field, int $subEventIndex, string $message)
    {
        parent::__construct($message);
        $this->field = $field;
        $this->subEventIndex = $subEventIndex;
    }

    public static function afterStart(int $subEventIndex) : self
    {
        return new self('childcare/start', $subEventIndex, 'childcare.start must be before the time portion of startDate');
    }

    public static function beforeEnd(int $subEventIndex) : self
    {
        return new self('childcare/end', $subEventIndex,  'childcare.end must be after the time portion of endDate');
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getSubEventIndex(): int
    {
        return $this->subEventIndex;
    }
}
