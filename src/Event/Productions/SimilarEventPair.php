<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

class SimilarEventPair
{
    private string $eventOne;

    private string $eventTwo;

    public function __construct(string $eventOne, string $eventTwo)
    {
        $this->eventOne = $eventOne;
        $this->eventTwo = $eventTwo;
    }

    public static function fromArray(array $eventIds): SimilarEventPair
    {
        if (!array_key_exists(0, $eventIds) || !array_key_exists(1, $eventIds)) {
            throw new \InvalidArgumentException();
        }

        return new self($eventIds[0], $eventIds[1]);
    }

    public function getEventOne(): string
    {
        return $this->eventOne;
    }

    public function getEventTwo(): string
    {
        return $this->eventTwo;
    }
}
