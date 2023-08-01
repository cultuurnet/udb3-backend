<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Organizer\Commands\UpdateEducationalDescription;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class UpdateEducationalDescriptionHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;

    public function __construct(OrganizerRepository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateEducationalDescription) {
            return;
        }

        $organizer = $this->organizerRepository->load($command->getItemId());

        $organizer->updateEducationalDescription($command->getEducationalDescription(), $command->getLanguage());

        $this->organizerRepository->save($organizer);
    }
}
