<?php

namespace CultuurNet\UDB3\Calendar;

use Broadway\Serializer\SerializableInterface;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Day;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;

/**
 * @todo Replace by CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days.
 */
class DayOfWeekCollection implements SerializableInterface
{
    /**
     * @var string[]
     */
    private $daysOfWeek = [];

    /**
     * DayOfWeekCollection constructor.
     * @param DayOfWeek[] ...$daysOfWeek
     */
    public function __construct(DayOfWeek ...$daysOfWeek)
    {
        array_walk($daysOfWeek, [$this, 'addDayOfWeek']);
    }

    /**
     * Keeps the collection of days of week unique.
     * Makes sure that the objects are stored as strings to allow PHP serialize method.
     *
     * @param DayOfWeek $dayOfWeek
     * @return DayOfWeekCollection
     */
    public function addDayOfWeek(DayOfWeek $dayOfWeek)
    {
        $this->daysOfWeek = array_unique(
            array_merge(
                $this->daysOfWeek,
                [
                    $dayOfWeek->toNative(),
                ]
            )
        );

        return $this;
    }

    /**
     * @return DayOfWeek[]
     */
    public function getDaysOfWeek()
    {
        return array_map(
            function ($dayOfWeek) {
                return DayOfWeek::fromNative($dayOfWeek);
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
                 return $collection->addDayOfWeek(DayOfWeek::fromNative($dayOfWeek));
            },
            new DayOfWeekCollection()
        );
    }

    /**
     * @inheritdoc
     */
    public function serialize()
    {
        return $this->daysOfWeek;
    }

    /**
     * @param Days $days
     * @return self
     */
    public static function fromUdb3ModelDays(Days $days)
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
