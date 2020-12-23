<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\CommandHandlers;

use Broadway\CommandHandling\CommandHandlerInterface;
use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Place\Commands\Status\UpdateStatus;
use CultuurNet\UDB3\Place\Place;

class UpdateStatusHandler implements CommandHandlerInterface
{
    /**
     * @var RepositoryInterface
     */
    private $placeRepository;

    public function __construct(RepositoryInterface $placeRepository)
    {
        $this->placeRepository = $placeRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof UpdateStatus)) {
            return;
        }

        /** @var Place $place */
        $place = $this->placeRepository->load($command->getItemId());
        $place->updateStatus($command->getStatus());
        $this->placeRepository->save($place);
    }
}
