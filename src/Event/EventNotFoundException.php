<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

class EventNotFoundException extends \Exception
{
    public function __construct(string $message = '', int $code = 404, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
