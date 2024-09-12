<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Popularity;

final class InMemoryPopularityRepository implements PopularityRepository
{
    private array $popularityScores = [];

    public function saveScore(string $offerId, Popularity $popularity): void
    {
        $this->popularityScores[$offerId] = $popularity;
    }

    public function get(string $offerId): Popularity
    {
        return $this->popularityScores[$offerId] ?? new Popularity(0);
    }
}
