<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

final class BirthYearRange
{
    private int $from;

    private int $to;

    public function __construct(int $from, int $to)
    {
        if ($from > $to) {
            throw new InvalidAgeRangeException('"From" birth year should not be greater than the "to" birth year.');
        }

        $this->from = $from;
        $this->to = $to;
    }

    public function getFrom(): int
    {
        return $this->from;
    }

    public function getTo(): int
    {
        return $this->to;
    }

    public static function fromString(string $birthYearRangeString): self
    {
        if (!str_contains($birthYearRangeString, '-')) {
            if (!is_numeric($birthYearRangeString)) {
                throw new InvalidAgeRangeException(
                    'The birth year should be a natural number.'
                );
            }
            $year = (int) $birthYearRangeString;
            return new self($year, $year);
        }

        $parts = explode('-', $birthYearRangeString);

        if (count($parts) !== 2) {
            throw new InvalidAgeRangeException(
                'Birth year range string is not valid because it has too many hyphens.'
            );
        }

        [$fromString, $toString] = $parts;

        if (!is_numeric($fromString)) {
            throw new InvalidAgeRangeException(
                'The "from" birth year should be a natural number.'
            );
        }

        if (!is_numeric($toString)) {
            throw new InvalidAgeRangeException(
                'The "to" birth year should be a natural number.'
            );
        }

        return new self((int) $fromString, (int) $toString);
    }

    public function toString(): string
    {
        if ($this->from === $this->to) {
            return (string) $this->from;
        }

        return $this->from . '-' . $this->to;
    }

    public function sameAs(BirthYearRange $other): bool
    {
        return $this->from === $other->from && $this->to === $other->to;
    }
}
