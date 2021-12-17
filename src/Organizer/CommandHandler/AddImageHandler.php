<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Organizer\Commands\AddImage;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class AddImageHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;

    public function __construct(OrganizerRepository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof AddImage) {
            return;
        }

        $organizer = $this->organizerRepository->load($command->getOrganizerId());

        $organizer->addImage($command->getImage());

        $this->organizerRepository->save($organizer);
    }
}
