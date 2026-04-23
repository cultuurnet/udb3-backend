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

    public static function fromArray(array $data): self
    {
        return new self(
            new DateTimeImmutable($data['from']),
            new DateTimeImmutable($data['to'])
        );
    }

    public function toArray(): array
    {
        return [
            'from' => $this->from->format('Y-m-d'),
            'to' => $this->to->format('Y-m-d'),
        ];
    }

    public static function fromBirthYearRange(BirthYearRange $birthYearRange): self
    {
        return new self(
            new DateTimeImmutable($birthYearRange->getFrom() . '-01-01'),
            new DateTimeImmutable($birthYearRange->getTo() . '-12-31')
        );
    }

    public function sameAs(BirthdateRange $other): bool
    {
        return $this->from->format('Y-m-d') === $other->from->format('Y-m-d')
            && $this->to->format('Y-m-d') === $other->to->format('Y-m-d');
    }
}
