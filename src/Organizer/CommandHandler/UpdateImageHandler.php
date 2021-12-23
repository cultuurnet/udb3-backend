<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Organizer\Commands\UpdateImage;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class UpdateImageHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;

    public function __construct(OrganizerRepository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateImage) {
            return;
        }

        $organizer = $this->organizerRepository->load($command->getItemId());

        $organizer->updateImage(
            $command->getImageID(),
            $command->getLanguage(),
            $command->getDescription(),
            $command->getCopyrightHolder()
        );

        $this->organizerRepository->save($organizer);
    }
}
