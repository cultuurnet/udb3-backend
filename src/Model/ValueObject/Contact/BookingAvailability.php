<?php

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\DateTimeImmutableRange;

final class BookingAvailability extends DateTimeImmutableRange
{
    public static function from(\DateTimeImmutable $from): BookingAvailability
    {
        return new self($from, null);
    }

    public static function to(\DateTimeImmutable $to): BookingAvailability
    {
        return new self(null, $to);
    }

    public static function fromTo(\DateTimeImmutable $from, \DateTimeImmutable $to): BookingAvailability
    {
        return new self($from, $to);
    }
}
