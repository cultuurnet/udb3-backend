<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Organizer\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class UpdateOrganizerHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;

    public function __construct(OrganizerRepository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateOrganizer) {
            return;
        }

        $organizer = $this->organizerRepository->load($command->getItemId());

        $organizer->updateOrganizer($command->getMainImageId());

        $this->organizerRepository->save($organizer);
    }
}
