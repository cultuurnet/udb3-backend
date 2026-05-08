<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

use DateTimeImmutable;

final class BirthdateRange
{
    public function __construct(
        private DateTimeImmutable $from,
        private DateTimeImmutable $to
    ) {
        if ($from > $to) {
            throw new InvalidAgeRangeException('"From" birthdate should not be greater than the "to" birthdate.');
        }
    }

    public function getFrom(): DateTimeImmutable
    {
        return $this->from;
    }

    public function getTo(): DateTimeImmutable
    {
        return $this->to;
    }

    public function sameAs(BirthdateRange $other): bool
    {
        return $this->from->format('Y-m-d') === $other->from->format('Y-m-d')
            && $this->to->format('Y-m-d') === $other->to->format('Y-m-d');
    }
}
