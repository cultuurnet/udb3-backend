<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Contributor\ContributorRepository;
use CultuurNet\UDB3\Event\Events\UpdateEventContributors;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Offer\Commands\UpdateContributors;
use CultuurNet\UDB3\Offer\OfferRepository;

final class UpdateContributorsHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    private ContributorRepository $contributorRepository;

    public function __construct(OfferRepository $offerRepository, ContributorRepository $contributorRepository)
    {
        $this->offerRepository = $offerRepository;
        $this->contributorRepository = $contributorRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof UpdateContributors)) {
            return;
        }

        // Load the offer to check that it actually exists
        $this->offerRepository->load($command->getItemId());

        $this->contributorRepository->overwriteContributors(
            new UUID($command->getItemId()),
            $command->getEmailAddresses(),
            $command instanceof UpdateEventContributors ? ItemType::event() : ItemType::place()
        );
    }
}