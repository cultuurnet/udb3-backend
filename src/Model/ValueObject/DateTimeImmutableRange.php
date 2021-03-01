<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject;

abstract class DateTimeImmutableRange
{
    /**
     * @var \DateTimeImmutable
     */
    private $from;

    /**
     * @var \DateTimeImmutable
     */
    private $to;


    public function __construct(\DateTimeImmutable $from = null, \DateTimeImmutable $to = null)
    {
        if ($from && $to && $from > $to) {
            throw new \InvalidArgumentException('"From" date should not be later than the "to" date.');
        }

        $this->from = $from;
        $this->to = $to;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return \DateTimeImmutable|null
     */
    public function getTo()
    {
        return $this->to;
    }
}
