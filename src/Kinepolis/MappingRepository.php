<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

interface MappingRepository
{
    public function getByMovieId(string $movieId): ?string;

    public function create(string $eventId, string $movieId): void;
}
