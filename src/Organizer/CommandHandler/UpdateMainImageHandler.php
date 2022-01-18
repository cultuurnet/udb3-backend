<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Organizer\Commands\UpdateMainImage;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class UpdateMainImageHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;

    public function __construct(OrganizerRepository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateMainImage) {
            return;
        }

        $organizer = $this->organizerRepository->load($command->getItemId());

        $organizer->updateMainImage($command->getImageId());

        $this->organizerRepository->save($organizer);
    }
}
