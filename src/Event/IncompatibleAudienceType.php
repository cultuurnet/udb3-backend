<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use CultuurNet\UDB3\Model\ValueObject\Audience\AudienceType;
use Exception;

final class IncompatibleAudienceType extends Exception
{
    public static function forEvent(string $eventId, AudienceType $audienceType): IncompatibleAudienceType
    {
        return new self('Audience type ' . $audienceType->toString() . ' is incompatible with event ' . $eventId);
    }
}
