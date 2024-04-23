<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Ownership\Commands\RequestOwnership;
use CultuurNet\UDB3\Ownership\Ownership;
use CultuurNet\UDB3\Ownership\OwnershipRepository;

final class RequestOwnershipHandler implements CommandHandler
{
    private OwnershipRepository $ownershipRepository;

    public function __construct(OwnershipRepository $ownershipRepository)
    {
        $this->ownershipRepository = $ownershipRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof RequestOwnership) {
            return;
        }

        $ownership = Ownership::requestOwnership(
            $command->getId(),
            $command->getItemId(),
            $command->getItemType(),
            $command->getOwnerId(),
            $command->getRequesterId()
        );

        $this->ownershipRepository->save($ownership);
    }
}
