<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Organizer\Commands\DeleteEducationalDescription;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class DeleteEducationalDescriptionHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;

    public function __construct(OrganizerRepository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof DeleteEducationalDescription) {
            return;
        }

        $organizer = $this->organizerRepository->load($command->getItemId());

        $organizer->deleteEducationalDescription($command->getLanguage());

        $this->organizerRepository->save($organizer);
    }
}
