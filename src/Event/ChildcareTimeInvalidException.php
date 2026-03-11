<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use InvalidArgumentException;

class ChildcareTimeInvalidException extends InvalidArgumentException
{
    private string $field;
    private int $subEventIndex;

    public function __construct(string $field, int $subEventIndex, string $message)
    {
        parent::__construct($message);
        $this->field = $field;
        $this->subEventIndex = $subEventIndex;
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
