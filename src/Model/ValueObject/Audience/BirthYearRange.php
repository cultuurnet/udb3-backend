<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

final class BirthYearRange
{
    private ?int $from;

    private ?int $to;

    public function __construct(?int $from = null, ?int $to = null)
    {
        if ($from !== null && $to !== null && $from > $to) {
            throw new InvalidAgeRangeException('"From" birth year should not be greater than the "to" birth year.');
        }

        $this->from = $from;
        $this->to = $to;
    }

    public function getFrom(): ?int
    {
        return $this->from;
    }

    public function getTo(): ?int
    {
        return $this->to;
    }

    public static function fromString(string $birthYearRangeString): self
    {
        $parts = explode('-', $birthYearRangeString);
        if (!isset($parts[1])) {
            throw new InvalidAgeRangeException(
                'Birth year range string is not valid because it is missing a hyphen.'
            );
        }

        if (count($parts) !== 2) {
            throw new InvalidAgeRangeException(
                'Birth year range string is not valid because it has too many hyphens.'
            );
        }

        [$fromString, $toString] = $parts;

        if (is_numeric($fromString) || empty($fromString)) {
            $from = is_numeric($fromString) ? (int) $fromString : null;
        } else {
            throw new InvalidAgeRangeException(
                'The "from" birth year should be a natural number or empty.'
            );
        }

        if (is_numeric($toString) || empty($toString)) {
            $to = is_numeric($toString) ? (int) $toString : null;
        } else {
            throw new InvalidAgeRangeException(
                'The "to" birth year should be a natural number or empty.'
            );
        }

        return new self($from, $to);
    }

    public function toString(): string
    {
        $from = $this->from !== null ? (string) $this->from : '';
        $to = $this->to !== null ? (string) $this->to : '';

        return $from . '-' . $to;
    }

    public function sameAs(BirthYearRange $other): bool
    {
        return $this->from === $other->from && $this->to === $other->to;
    }
}
