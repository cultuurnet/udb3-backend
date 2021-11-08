<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

interface PlaceServiceInterface
{
    /**
     * @return string[]
     */
    public function placesOrganizedByOrganizer(string $organizerId): array;
}
