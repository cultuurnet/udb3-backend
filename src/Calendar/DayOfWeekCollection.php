<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days instead where possible.
 */
class DayOfWeekCollection implements Serializable
{
    /**
     * @var string[]
     */
    private array $daysOfWeek = [];

    public function __construct(DayOfWeek ...$daysOfWeek)
    {
        array_walk($daysOfWeek, [$this, 'addDayOfWeek']);
    }

    /**
     * Keeps the collection of days of week unique.
     * Makes sure that the objects are stored as strings to allow PHP serialize method.
     */
    public function addDayOfWeek(DayOfWeek $dayOfWeek): DayOfWeekCollection
    {
        $this->daysOfWeek = array_unique(
            array_merge(
                $this->daysOfWeek,
                [
                    $dayOfWeek->toString(),
                ]
            )
        );

        return $this;
    }

    /**
     * @return DayOfWeek[]
     */
    public function getDaysOfWeek(): array
    {
        return array_map(
            function ($dayOfWeek) {
                return new DayOfWeek($dayOfWeek);
            },
            $this->daysOfWeek
        );
    }

    /**
     * @inheritdoc
     */
    public static function deserialize(array $data)
    {
        return array_reduce(
            $data,
            function (DayOfWeekCollection $collection, $dayOfWeek) {
                return $collection->addDayOfWeek(new DayOfWeek($dayOfWeek));
            },
            new DayOfWeekCollection()
        );
    }

    public function serialize(): array
    {
        return $this->daysOfWeek;
    }

    public static function fromUdb3ModelDays(Days $days): DayOfWeekCollection
    {
        $days = array_map(
            function (Day $day) {
                return DayOfWeek::fromUdb3ModelDay($day);
            },
            $days->toArray()
        );

        return new self(...$days);
    }
}
