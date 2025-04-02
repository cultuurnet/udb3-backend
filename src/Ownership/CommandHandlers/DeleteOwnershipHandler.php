<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Ownership\Commands\DeleteOwnership;
use CultuurNet\UDB3\Ownership\OwnershipRepository;

final class DeleteOwnershipHandler implements CommandHandler
{
    private OwnershipRepository $ownershipRepository;

    public function __construct(OwnershipRepository $ownershipRepository)
    {
        $this->ownershipRepository = $ownershipRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof DeleteOwnership) {
            return;
        }

        $ownership = $this->ownershipRepository->load($command->getId()->toString());

        $ownership->delete($command->getUserId());

        $this->ownershipRepository->save($ownership);
    }
}
