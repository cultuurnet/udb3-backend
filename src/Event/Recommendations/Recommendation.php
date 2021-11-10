<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Recommendations;

final class Recommendation
{
    private string $event;

    private float $score;

    public function __construct(string $event, float $score)
    {
        $this->event = $event;
        $this->score = $score;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getScore(): float
    {
        return $this->score;
    }
}
