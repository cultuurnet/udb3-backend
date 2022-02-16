<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UDB2\DomainEvents;

use DateTimeImmutable;
use DateTimeInterface;
use CultuurNet\UDB3\StringLiteral;

class ISO8601DateTimeDeserializer
{
    public static function deserialize(StringLiteral $timeString): DateTimeImmutable
    {
        $time = DateTimeImmutable::createFromFormat(
            DateTimeInterface::ATOM,
            $timeString->toNative()
        );

        if (!$time instanceof DateTimeImmutable) {
            // @todo Replace with a more specific exception.
            $now = new DateTimeImmutable();
            throw new \RuntimeException(
                'invalid time provided, please use a ISO 8601 formatted date' .
                '& time like ' . $now->format(DateTimeInterface::ATOM)
            );
        }

        return $time;
    }
}
