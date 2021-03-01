<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class ImportLabelsHandler implements CommandHandler
{
    /**
     * @var OrganizerRepository
     */
    private $organizerRepository;

    public function __construct(OrganizerRepository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof ImportLabels)) {
            return;
        }

        $organizer = $this->organizerRepository->load($command->getOrganizerId());
        $organizer->importLabels($command->getLabels(), $command->getLabelsToKeepIfAlreadyOnOrganizer());
        $this->organizerRepository->save($organizer);
    }
}
