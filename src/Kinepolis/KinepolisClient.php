<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Kinepolis;

use GuzzleHttp\Psr7\UploadedFile;

interface KinepolisClient
{
    public function getToken(): string;

    public function getMovies(string $token): array;

    public function getMovieDetail(string $token, int $mid): array;

    public function getImage(string $token, string $path): UploadedFile;

    public function getTheaters(string $token): array;

    public function getPricesForATheater(string $token, string $tid): array;
}
