<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange as Udb3ModelAgeRange;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange where possible.
 */
class AgeRange
{
    private ?Age $from;

    private ?Age $to;

    /**
     * @throws InvalidAgeRangeException
     */
    public function __construct(?Age $from = null, ?Age $to = null)
    {
        // Make sure not to convert " - " which is all ages to "0- " which would apply to Vlieg
        // So only convert " -X" to "0-X"
        if ($from === null && $to !== null) {
            $from = new Age(0);
        }

        $this->guardValidAgeRange($from, $to);

        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @throws InvalidAgeRangeException
     */
    private function guardValidAgeRange(?Age $from, ?Age $to = null): void
    {
        if ($from && $to && $from > $to) {
            throw new InvalidAgeRangeException('"from" age should not exceed "to" age');
        }
    }

    public function getFrom(): ?Age
    {
        return $this->from;
    }

    public function getTo(): ?Age
    {
        return $this->to;
    }

    public function __toString(): string
    {
        $from = $this->from ? $this->from->toInteger() : '';
        $to = $this->to ? $this->to->toInteger() : '';

        return $from . '-' . $to;
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

    public function sameAs(AgeRange $otherAgeRange): bool
    {
        return (string) $this === (string) $otherAgeRange;
    }

    public static function fromUbd3ModelAgeRange(Udb3ModelAgeRange $udb3ModelAgeRange): AgeRange
    {
        $from = null;
        if ($from = $udb3ModelAgeRange->getFrom()) {
            $from = new Age($from->toInteger());
        }

        $to = null;
        if ($to = $udb3ModelAgeRange->getTo()) {
            $to = new Age($to->toInteger());
        }

        return new AgeRange($from, $to);
    }
}
