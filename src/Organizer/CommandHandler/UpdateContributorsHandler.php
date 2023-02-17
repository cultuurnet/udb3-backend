<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Organizer\Commands\UpdateContributors;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class UpdateContributorsHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;

    private ContributorRepository $contributorRepository;

    public function __construct(OrganizerRepository $organizerRepository, ContributorRepository $contributorRepository)
    {
        $this->organizerRepository = $organizerRepository;
        $this->contributorRepository = $contributorRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof UpdateContributors)) {
            return;
        }

        // Load the organizer to check that it actually exists
        $this->organizerRepository->load($command->getItemId());

        $this->contributorRepository->updateContributors(
            new UUID($command->getItemId()),
            $command->getEmailAddresses(),
            ItemType::organizer()
        );
    }
}
