<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Organizer\Commands\RemoveAddress;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class RemoveAddressHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;

    public function __construct(OrganizerRepository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof RemoveAddress) {
            return;
        }

        $organizer = $this->organizerRepository->load($command->getItemId());

        $organizer->removeAddress();

        $this->organizerRepository->save($organizer);
    }
}
