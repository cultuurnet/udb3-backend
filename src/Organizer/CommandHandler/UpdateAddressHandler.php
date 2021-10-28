<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Organizer\Commands\UpdateAddress;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class UpdateAddressHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;

    public function __construct(OrganizerRepository $organizerRepository)
    {
        $this->organizerRepository = $organizerRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateAddress) {
            return;
        }

        $organizer = $this->organizerRepository->load($command->getItemId());

        $organizer->updateAddress($command->getAddress(), Language::fromUdb3ModelLanguage($command->getLanguage()));

        $this->organizerRepository->save($organizer);
    }
}
