<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

class Suggestion
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

    public function getEventOne(): string
    {
        return $this->eventOne;
    }

    public function getEventTwo(): string
    {
        return $this->eventTwo;
    }
}
