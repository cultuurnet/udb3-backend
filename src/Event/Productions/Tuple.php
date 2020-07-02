<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

class Tuple
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

    public function asArray(): array
    {
        return [
            'event1' => $this->eventOne,
            'event2' => $this->eventTwo
        ];
    }
}
