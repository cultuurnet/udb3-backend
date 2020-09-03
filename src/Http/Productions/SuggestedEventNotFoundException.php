<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

final class SuggestedEventNotFoundException extends \Exception
{
    public function __construct(string $eventId, \Exception $previous = null)
    {
        parent::__construct("The suggested event with id ${eventId} could not be found.", 404, $previous);
    }
}
