<?php

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

class AgeRange
{
    /**
     * @var ?Age
     */
    private $from;

    /**
     * @var ?Age
     */
    private $to;

    public function __construct(?Age $from = null, ?Age $to = null)
    {
        // Make sure not to convert " - " which is all ages to "0- " which would apply to Vlieg
        // So only convert " -X" to "0-X"
        if ($from === null && $to !== null) {
            $from = new Age(0);
        }

        if ($from && $to && $from->gt($to)) {
            throw new \InvalidArgumentException('"From" age should not be greater than the "to" age.');
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
}
