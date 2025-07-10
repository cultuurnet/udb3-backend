<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;

class CanonicalPlaceRepository
{
    private DuplicatePlaceRepository $duplicatePlaceRepository;

    public function __construct(
        DuplicatePlaceRepository $duplicatePlaceRepository
    ) {
        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
    }

    public function findCanonicalIdFor(string $placeId): ?string
    {
        return $this->duplicatePlaceRepository->getCanonicalOfPlace($placeId);
    }
}
