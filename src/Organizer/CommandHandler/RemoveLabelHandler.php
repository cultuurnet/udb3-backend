<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Organizer\Commands\RemoveLabel;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class RemoveLabelHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;

    public function __construct(OrganizerRepository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof RemoveLabel)) {
            return;
        }

        $labelName = $command->getLabelName();

        $organizer = $this->organizerRepository->load($command->getItemId());
        $organizer->removeLabel($labelName);
        $this->organizerRepository->save($organizer);
    }
}
