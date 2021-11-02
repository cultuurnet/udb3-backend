<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Organizer\Commands\UpdateWebsite;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use ValueObjects\Web\Url;

final class UpdateWebsiteHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;

    public function __construct(OrganizerRepository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateWebsite) {
            return;
        }

        $organizer = $this->organizerRepository->load($command->getItemId());

        $organizer->updateWebsite(Url::fromNative($command->getWebsite()->toString()));

        $this->organizerRepository->save($organizer);
    }
}
