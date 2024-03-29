<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour as Udb3ModelOpeningHour;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour instead where possible.
 */
final class OpeningHour implements Serializable
{
    private OpeningTime $opens;

    private OpeningTime $closes;

    private DayOfWeekCollection $dayOfWeekCollection;

    public function __construct(
        OpeningTime $opens,
        OpeningTime $closes,
        DayOfWeekCollection $dayOfWeekCollection
    ) {
        $this->dayOfWeekCollection = $dayOfWeekCollection;
        $this->opens = $opens;
        $this->closes = $closes;
    }

    public function getOpens(): OpeningTime
    {
        return $this->opens;
    }

    public function getCloses(): OpeningTime
    {
        return $this->closes;
    }

    public function getDayOfWeekCollection(): DayOfWeekCollection
    {
        return $this->dayOfWeekCollection;
    }

    public function addDayOfWeekCollection(DayOfWeekCollection $dayOfWeekCollection): void
    {
        foreach ($dayOfWeekCollection->getDaysOfWeek() as $dayOfWeek) {
            $this->dayOfWeekCollection->addDayOfWeek($dayOfWeek);
        }
    }

    public function hasEqualHours(OpeningHour $otherOpeningHour): bool
    {
        return $otherOpeningHour->getOpens()->sameValueAs($this->getOpens()) &&
            $otherOpeningHour->getCloses()->sameValueAs($this->getCloses());
    }

    public static function deserialize(array $data): OpeningHour
    {
        return new static(
            OpeningTime::fromNativeString($data['opens']),
            OpeningTime::fromNativeString($data['closes']),
            DayOfWeekCollection::deserialize($data['dayOfWeek'])
        );
    }

    public function serialize(): array
    {
        return [
            'opens' => $this->opens->toNativeString(),
            'closes' => $this->closes->toNativeString(),
            'dayOfWeek' => $this->dayOfWeekCollection->serialize(),
        ];
    }

    public static function fromUdb3ModelOpeningHour(Udb3ModelOpeningHour $openingHour): OpeningHour
    {
        return new self(
            OpeningTime::fromUdb3ModelTime($openingHour->getOpeningTime()),
            OpeningTime::fromUdb3ModelTime($openingHour->getClosingTime()),
            DayOfWeekCollection::fromUdb3ModelDays($openingHour->getDays())
        );
    }
}
