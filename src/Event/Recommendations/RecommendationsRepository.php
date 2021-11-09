<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Recommendations;

interface RecommendationsRepository
{
    public function getByEvent(string $eventId): Recommendations;

    public function getByRecommendedEvent(string $eventId): Recommendations;
}
