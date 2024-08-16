<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Calendar;

use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Hour;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Minute;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;

/**
 * @deprecated
 *   Use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time instead where possible.
 */
class OpeningTime
{
    private Hour $hour;

    private Minute $minute;

    public function __construct(Hour $hour, Minute $minute)
    {
        $this->hour = $hour;
        $this->minute = $minute;
    }

    public static function fromNativeDateTime(\DateTimeInterface $dateTime): OpeningTime
    {
        $hour = new Hour((int) ($dateTime->format('H')));
        $minute = new Minute((int) ($dateTime->format('i')));

        return new OpeningTime($hour, $minute);
    }

    /**
     * The supported string format is H:i
     */
    public static function fromNativeString(string $time): OpeningTime
    {
        return self::fromNativeDateTime(
            DateTimeFactory::fromFormat('H:i', $time)
        );
    }

    public function toNativeString(): string
    {
        return (string) $this;
    }

    public function getHour(): Hour
    {
        return $this->hour;
    }

    public function getMinute(): Minute
    {
        return $this->minute;
    }

    public function sameValueAs(OpeningTime $time): bool
    {
        return $this->getHour()->sameAs($time->getHour()) &&
            $this->getMinute()->sameAs($time->getMinute());
    }

    public function __toString(): string
    {
        return $this->toNativeDateTime()->format('H:i');
    }

    private function toNativeDateTime(): \DateTimeInterface
    {
        $hour   = $this->getHour()->toInteger();
        $minute = $this->getMinute()->toInteger();

        $time = new \DateTime('now');
        $time->setTime($hour, $minute);

        return $time;
    }

    public static function fromUdb3ModelTime(Time $time): OpeningTime
    {
        $hour = new Hour($time->getHour()->toInteger());
        $minute = new Minute($time->getMinute()->toInteger());
        return new self($hour, $minute);
    }
}
