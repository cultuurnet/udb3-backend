<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\DaysDenormalizer;
use CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar\DaysNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Days;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour as Udb3ModelOpeningHour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHour instead where possible.
 */
final class OpeningHour implements Serializable
{
    private Time $opens;

    private Time $closes;

    private Days $dayOfWeekCollection;

    public function __construct(
        Time $opens,
        Time $closes,
        Days $dayOfWeekCollection
    ) {
        $this->dayOfWeekCollection = $dayOfWeekCollection;
        $this->opens = $opens;
        $this->closes = $closes;
    }

    public function getOpens(): Time
    {
        return $this->opens;
    }

    public function getCloses(): Time
    {
        return $this->closes;
    }

    public function getDays(): Days
    {
        return $this->dayOfWeekCollection;
    }

    public function addDays(Days $dayOfWeekCollection): void
    {
        foreach ($dayOfWeekCollection->getIterator() as $dayOfWeek) {
            $this->dayOfWeekCollection = $this->dayOfWeekCollection->with($dayOfWeek);
        }
    }

    public function hasEqualHours(OpeningHour $otherOpeningHour): bool
    {
        return $otherOpeningHour->getOpens()->sameAs($this->getOpens()) &&
            $otherOpeningHour->getCloses()->sameAs($this->getCloses());
    }

    public static function deserialize(array $data): OpeningHour
    {
        return new static(
            Time::fromString($data['opens']),
            Time::fromString($data['closes']),
            (new DaysDenormalizer())->denormalize($data['dayOfWeek'], Days::class)
        );
    }

    public function serialize(): array
    {
        return [
            'opens' => $this->opens->toString(),
            'closes' => $this->closes->toString(),
            'dayOfWeek' => (new DaysNormalizer())->normalize($this->dayOfWeekCollection),
        ];
    }

    public static function fromUdb3ModelOpeningHour(Udb3ModelOpeningHour $openingHour): OpeningHour
    {
        return new self(
            $openingHour->getOpeningTime(),
            $openingHour->getClosingTime(),
            $openingHour->getDays()
        );
    }
}
