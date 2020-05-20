<?php

namespace CultuurNet\UDB3\Place;

use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\LocalEntityService;
use CultuurNet\UDB3\Place\ReadModel\Relations\RepositoryInterface as RelationsRepository;

class LocalPlaceService extends LocalEntityService implements PlaceServiceInterface
{
    /**
     * @var RelationsRepository
     */
    private $placeRelationsRepository;

    public function __construct(
        DocumentRepositoryInterface $documentRepository,
        RepositoryInterface $entityRepository,
        RelationsRepository $placeRelationsRepository,
        IriGeneratorInterface $iriGenerator
    ) {
        parent::__construct($documentRepository, $entityRepository, $iriGenerator);

        $this->placeRelationsRepository = $placeRelationsRepository;
    }

    /**
     * @inheritdoc
     */
    public function placesOrganizedByOrganizer($organizerId)
    {
        return $this->placeRelationsRepository->getPlacesOrganizedByOrganizer(
            $organizerId
        );
    }
}
