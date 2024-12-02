<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

class AgeRange
{
    private ?Age $from;

    private ?Age $to;

    public function __construct(?Age $from = null, ?Age $to = null)
    {
        // Make sure not to convert " - " which is all ages to "0- " which would apply to Vlieg
        // So only convert " -X" to "0-X"
        if ($from === null && $to !== null) {
            $from = new Age(0);
        }

        if ($from && $to && $from->gt($to)) {
            throw new InvalidAgeRangeException('"From" age should not be greater than the "to" age.');
        }

        $this->from = $from;
        $this->to = $to;
    }

    public function getFrom(): ?Age
    {
        return $this->from;
    }

    public function getTo(): ?Age
    {
        return $this->to;
    }

    public static function from(Age $from): AgeRange
    {
        return new self($from, null);
    }

    public static function to(Age $to): AgeRange
    {
        return new self(null, $to);
    }

    public static function exactly(Age $age): AgeRange
    {
        return new self($age, $age);
    }

    public static function fromTo(Age $from, Age $to): AgeRange
    {
        return new self($from, $to);
    }

    public static function any(): AgeRange
    {
        return new self();
    }

    /**
     * @throws InvalidAgeRangeException
     */
    public static function fromString(string $ageRangeString): AgeRange
    {
        $stringValues = explode('-', $ageRangeString);
        if (!isset($stringValues[1])) {
            throw new InvalidAgeRangeException(
                'Date-range string is not valid because it is missing a hyphen.'
            );
        }

        if (count($stringValues) !== 2) {
            throw new InvalidAgeRangeException(
                'Date-range string is not valid because it has too many hyphens.'
            );
        }

        [$fromString, $toString] = $stringValues;

        if (is_numeric($fromString) || empty($fromString)) {
            $from = is_numeric($fromString) ? new Age((int) $fromString) : null;
        } else {
            throw new InvalidAgeRangeException(
                'The "from" age should be a natural number or empty.'
            );
        }

        if (is_numeric($toString) || empty($toString)) {
            $to = is_numeric($toString) ? new Age((int) $toString) : null;
        } else {
            throw new InvalidAgeRangeException(
                'The "to" age should be a natural number or empty.'
            );
        }

        return new self($from, $to);
    }

    public function toString(): string
    {
        $from = $this->from ? $this->from->toInteger() : '';
        $to = $this->to ? $this->to->toInteger() : '';

        return $from . '-' . $to;
    }
}
