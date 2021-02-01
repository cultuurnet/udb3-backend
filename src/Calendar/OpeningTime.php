<?php

namespace CultuurNet\UDB3\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\Time;
use ValueObjects\DateTime\Hour;
use ValueObjects\DateTime\Minute;

class OpeningTime
{
    /**
     * @var Hour
     */
    private $hour;

    /**
     * @var Minute
     */
    private $minute;

    /**
     * Custom value object for opening times without seconds.
     *
     * @param Hour $hour
     * @param Minute $minute
     */
    public function __construct(Hour $hour, Minute $minute)
    {
        $this->hour = $hour;
        $this->minute = $minute;
    }

    /**
     * @param \DateTimeInterface $dateTime
     * @return OpeningTime
     */
    public static function fromNativeDateTime(\DateTimeInterface $dateTime)
    {
        $hour = new Hour(\intval($dateTime->format('H')));
        $minute = new Minute(\intval($dateTime->format('i')));

        return new OpeningTime($hour, $minute);
    }

    /**
     * The supported string format is H:i
     *
     * @param string $time
     * @return OpeningTime
     */
    public static function fromNativeString($time)
    {
        return self::fromNativeDateTime(
            \DateTime::createFromFormat('H:i', $time)
        );
    }

    /**
     * @return string
     */
    public function toNativeString()
    {
        return (string) $this;
    }

    /**
     * @return Hour
     */
    public function getHour()
    {
        return $this->hour;
    }

    /**
     * @return Minute
     */
    public function getMinute()
    {
        return $this->minute;
    }

    /**
     * @param OpeningTime $time
     * @return bool
     */
    public function sameValueAs(OpeningTime $time)
    {
        return $this->getHour()->sameValueAs($time->getHour()) &&
            $this->getMinute()->sameValueAs($time->getMinute());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->toNativeDateTime()->format('H:i');
    }

    /**
     * @return \DateTimeInterface
     */
    private function toNativeDateTime()
    {
        $hour   = $this->getHour()->toNative();
        $minute = $this->getMinute()->toNative();

        $time = new \DateTime('now');
        $time->setTime($hour, $minute);

        return $time;
    }

    /**
     * @param Time $time
     * @return self
     */
    public static function fromUdb3ModelTime(Time $time)
    {
        $hour = new Hour($time->getHour()->toInteger());
        $minute = new Minute($time->getMinute()->toInteger());
        return new self($hour, $minute);
    }
}
