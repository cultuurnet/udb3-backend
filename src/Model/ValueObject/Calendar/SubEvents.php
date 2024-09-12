<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Collection\Behaviour\IsNotEmpty;
use CultuurNet\UDB3\Model\ValueObject\Collection\Collection;

class SubEvents extends Collection
{
    use IsNotEmpty;

    /**
     * @var \DateTimeImmutable
     */
    private $startDate;

    /**
     * @var \DateTimeImmutable
     */
    private $endDate;

    /**
     * @param SubEvent ...$subEvents
     */
    public function __construct(SubEvent ...$subEvents)
    {
        $this->guardNotEmpty($subEvents);

        usort(
            $subEvents,
            function (SubEvent $a, SubEvent $b) {
                return $a->getDateRange()->compare($b->getDateRange());
            }
        );

        parent::__construct(...$subEvents);

        if (count($subEvents) > 0) {
            $this->startDate = $this->getFirst()->getDateRange()->getFrom();
            $this->endDate = $this->getLast()->getDateRange()->getTo();
        }
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }
}
