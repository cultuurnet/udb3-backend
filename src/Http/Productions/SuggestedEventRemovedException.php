<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Http\Productions;

final class SuggestedEventRemovedException extends \Exception
{
    public function __construct(string $eventId, \Exception $previous = null)
    {
        parent::__construct("The suggested event with id ${eventId} was removed in the past.", 404, $previous);
    }
}
