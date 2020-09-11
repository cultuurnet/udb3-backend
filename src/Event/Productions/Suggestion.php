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

    /**
     * @var float
     */
    private $similarity;

    public function __construct(string $eventOne, string $eventTwo, float $similarity)
    {
        $this->eventOne = $eventOne;
        $this->eventTwo = $eventTwo;
        $this->similarity = $similarity;
    }

    public function getEventOne(): string
    {
        return $this->eventOne;
    }

    public function getEventTwo(): string
    {
        return $this->eventTwo;
    }

    public function getSimilarity(): float
    {
        return $this->similarity;
    }
}
