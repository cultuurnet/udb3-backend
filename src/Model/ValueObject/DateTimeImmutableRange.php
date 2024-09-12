<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject;

abstract class DateTimeImmutableRange
{
    private ?\DateTimeImmutable $from = null;

    private ?\DateTimeImmutable $to = null;

    public function __construct(\DateTimeImmutable $from = null, \DateTimeImmutable $to = null)
    {
        if ($from && $to && $from > $to) {
            throw new \InvalidArgumentException('"From" date should not be later than the "to" date.');
        }

        $this->from = $from;
        $this->to = $to;
    }

    public function getFrom(): ?\DateTimeImmutable
    {
        return $this->from;
    }

    public function getTo(): ?\DateTimeImmutable
    {
        return $this->to;
    }
}
