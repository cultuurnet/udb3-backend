<?php

namespace CultuurNet\UDB3\Offer;

use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange as Udb3ModelAgeRange;
use ValueObjects\Person\Age;

class AgeRange
{
    /**
     * @var null|Age
     */
    private $from;

    /**
     * @var null|Age
     */
    private $to;

    /**
     * AgeRange constructor.
     * @param null|Age $from
     * @param null|Age $to
     */
    public function __construct(Age $from = null, Age $to = null)
    {
        $from = $from ?: new Age(0);

        $this->guardValidAgeRange($from, $to);

        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @param Age $from
     * @param null|Age $to
     *
     * @throws InvalidAgeRangeException
     */
    private function guardValidAgeRange(Age $from, Age $to = null)
    {
        if ($from && $to && $from > $to) {
            throw new InvalidAgeRangeException('"from" age should not exceed "to" age');
        }
    }

    /**
     * @return Age
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return null|Age
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $from = $this->from ? (string) $this->from : '';
        $to = $this->to ? (string) $this->to : '';

        return $from . '-' . $to;
    }

    /**
     * @param string $ageRangeString
     * @return AgeRange
     *
     * @throws InvalidAgeRangeException
     */
    public static function fromString($ageRangeString)
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

        $fromString = $stringValues[0];
        $toString = $stringValues[1];

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

    /**
     * @param AgeRange $otherAgeRange
     * @return bool
     */
    public function sameAs(AgeRange $otherAgeRange)
    {
        return "$this" === "$otherAgeRange";
    }

    /**
     * @param Udb3ModelAgeRange $udb3ModelAgeRange
     * @return AgeRange
     */
    public static function fromUbd3ModelAgeRange(Udb3ModelAgeRange $udb3ModelAgeRange)
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
