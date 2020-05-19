<?php

namespace CultuurNet\UDB3\Place;

use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Place\Commands\MarkAsDuplicate;

class MarkAsDuplicateCommandHandler extends Udb3CommandHandler
{
    /**
     * @var PlaceRepository
     */
    private $repository;

    public function __construct(PlaceRepository $repository)
    {
        $this->repository = $repository;
    }

    public function handleMarkAsDuplicate(MarkAsDuplicate $command): void
    {
        /* @var Place $placeToMarkAsDuplicate */
        $placeToMarkAsDuplicate = $this->repository->load($command->getDuplicatePlaceId());

        /* @var Place $placeToMarkAsMaster */
        $placeToMarkAsMaster = $this->repository->load($command->getCanonicalPlaceId());

        $placeToMarkAsDuplicate->markAsDuplicateOf($command->getCanonicalPlaceId());
        $placeToMarkAsMaster->markAsCanonicalFor($command->getDuplicatePlaceId(), $placeToMarkAsDuplicate->getDuplicates());

        $this->repository->saveMultiple($placeToMarkAsDuplicate, $placeToMarkAsMaster);
    }
}
