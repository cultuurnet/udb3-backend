<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

class CanonicalPlaceRepository
{
    private PlaceRepository $repository;

    public function __construct(PlaceRepository $repository)
    {
        $this->repository = $repository;
    }

    public function findCanonicalFor(string $placeId): Place
    {
        /** @var Place $place */
        $place = $this->repository->load($placeId);
        while ($place->getCanonicalPlaceId()) {
            /** @var Place $place */
            $place = $this->repository->load($place->getCanonicalPlaceId());
        }

        return $place;
    }
}
