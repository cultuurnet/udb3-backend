<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use InvalidArgumentException;

class ChildcareTimeInvalidException extends InvalidArgumentException
{
    private string $jsonPointer;

    public function __construct(string $jsonPointer, string $message)
    {
        parent::__construct($message);
        $this->jsonPointer = $jsonPointer;
    }

    public function getJsonPointer(): string
    {
        return $this->jsonPointer;
    }
}
