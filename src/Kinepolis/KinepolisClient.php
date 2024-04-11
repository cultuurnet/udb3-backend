<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

interface KinepolisClient
{
    public function getToken(): string;

    public function getMovies(string $token): array;

    public function getMovieDetail(string $token, int $mid): array;

    public function getPrices(string $token, string $tid): array;
}
