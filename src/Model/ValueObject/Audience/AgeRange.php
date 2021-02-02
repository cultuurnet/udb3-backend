<?php

namespace CultuurNet\UDB3\Model\ValueObject\Audience;

class AgeRange
{
    /**
     * @var Age
     */
    private $from;

    /**
     * @var Age
     */
    private $to;

    /**
     * @param Age|null $from
     * @param Age|null $to
     */
    public function __construct(Age $from = null, Age $to = null)
    {
        $from = $from ?: new Age(0);

        if ($from && $to && $from->gt($to)) {
            throw new \InvalidArgumentException('"From" age should not be greater than the "to" age.');
        }

        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @return Age
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return Age|null
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * @param Age $from
     * @return AgeRange
     */
    public static function from(Age $from)
    {
        return new self($from, null);
    }

    /**
     * @param Age $to
     * @return AgeRange
     */
    public static function to(Age $to)
    {
        return new self(null, $to);
    }

    /**
     * @param Age $age
     * @return AgeRange
     */
    public static function exactly(Age $age)
    {
        return new self($age, $age);
    }

    /**
     * @param Age $from
     * @param Age $to
     * @return AgeRange
     */
    public static function fromTo(Age $from, Age $to)
    {
        return new self($from, $to);
    }

    /**
     * @return AgeRange
     */
    public static function any()
    {
        return new self();
    }
}
