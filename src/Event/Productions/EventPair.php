<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

class EventPair
{
    /**
     * @var string
     */
    private $eventOne;

    /**
     * @var string
     */
    private $eventTwo;

    public function __construct(string $eventOne, string $eventTwo)
    {
        $this->eventOne = $eventOne;
        $this->eventTwo = $eventTwo;
    }

    public static function fromArray(array $eventIds) : EventPair
    {
        if (!array_key_exists(0, $eventIds) || !array_key_exists(1, $eventIds)) {
            throw new \InvalidArgumentException();
        }

        return new self($eventIds[0], $eventIds[1]);
    }

    public function asArray(): array
    {
        return [
            'event1' => $this->eventOne,
            'event2' => $this->eventTwo,
        ];
    }
}
