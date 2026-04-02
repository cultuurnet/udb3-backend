<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use Exception;

final class IncompatibleAudienceType extends Exception
{
    public static function forDummyPlaceForEducation(string $eventId, AudienceType $audienceType): self
    {
        return new self(
            'Audience type "' . $audienceType->toString() . '" is not allowed for events with a dummy place for education. Event: ' . $eventId
        );
    }

    public static function forDeparturePlaces(string $eventId): self
    {
        return new self(
            'Departure places can only be set on events with audienceType "childrenOnly". Event: ' . $eventId
        );
    }
}
