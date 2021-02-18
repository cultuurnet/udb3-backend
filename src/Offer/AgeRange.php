<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange as Udb3ModelAgeRange;
use ValueObjects\Person\Age;

class AgeRange
{
    /**
     * @var Age
     */
    private $from;

    /**
     * @var ?Age
     */
    private $to;

    /**
     * @throws InvalidAgeRangeException
     */
    public function __construct(?Age $from = null, ?Age $to = null)
    {
        $from = $from ?: new Age(0);

        $this->guardValidAgeRange($from, $to);

        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @throws InvalidAgeRangeException
     */
    private function guardValidAgeRange(Age $from, ?Age $to = null): void
    {
        if ($from && $to && $from > $to) {
            throw new InvalidAgeRangeException('"from" age should not exceed "to" age');
        }
    }

    public function getFrom(): Age
    {
        return $this->from;
    }

    public function getTo(): ?Age
    {
        return $this->to;
    }

    public function __toString(): string
    {
        $from = $this->from ? (string) $this->from : '';
        $to = $this->to ? (string) $this->to : '';

        return $from . '-' . $to;
    }

    /**
     * @throws InvalidAgeRangeException
     */
    public static function fromString($ageRangeString): AgeRange
    {
        if (!is_string($ageRangeString)) {
            throw new InvalidAgeRangeException(
                'Date-range should be of type string.'
            );
        }

        $stringValues = explode('-', $ageRangeString);

        if (empty($stringValues) || !isset($stringValues[1])) {
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
            $from = is_numeric($fromString) ? new Age($fromString) : null;
        } else {
            throw new InvalidAgeRangeException(
                'The "from" age should be a natural number or empty.'
            );
        }

        if (is_numeric($toString) || empty($toString)) {
            $to = is_numeric($toString) ? new Age($toString) : null;
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
