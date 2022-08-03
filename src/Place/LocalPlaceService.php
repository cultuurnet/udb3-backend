<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place;

use Broadway\Repository\Repository;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\LocalEntityService;
use CultuurNet\UDB3\Place\ReadModel\Relations\PlaceRelationsRepository as RelationsRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;

class LocalPlaceService extends LocalEntityService implements PlaceServiceInterface
{
    private RelationsRepository $placeRelationsRepository;

    public function __construct(
        DocumentRepository $documentRepository,
        Repository $entityRepository,
        RelationsRepository $placeRelationsRepository,
        IriGeneratorInterface $iriGenerator
    ) {
        parent::__construct($documentRepository, $entityRepository, $iriGenerator);

        $this->placeRelationsRepository = $placeRelationsRepository;
    }

    public function placesOrganizedByOrganizer(string $organizerId): array
    {
        return $this->placeRelationsRepository->getPlacesOrganizedByOrganizer(
            $organizerId
        );
    }
}
