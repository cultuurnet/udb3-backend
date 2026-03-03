<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Contact;

use CultuurNet\UDB3\Model\ValueObject\DateTimeImmutableRange;

final class BookingDateRange extends DateTimeImmutableRange
{
    public static function from(\DateTimeImmutable $from): BookingDateRange
    {
        return new self($from, null);
    }

    public static function to(\DateTimeImmutable $to): BookingDateRange
    {
        return new self(null, $to);
    }

    public static function fromTo(\DateTimeImmutable $from, \DateTimeImmutable $to): BookingDateRange
    {
        return new self($from, $to);
    }
}
