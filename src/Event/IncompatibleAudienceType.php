<?php

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Event\ValueObjects\AudienceType;
use Exception;

final class IncompatibleAudienceType extends Exception
{
    public static function forEvent(string $eventId, AudienceType $audienceType)
    {
        return new self('Audience type ' . $audienceType->toNative() . ' is incompatible with event ' . $eventId);
    }
}
