<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\Status\UpdateStatus;
use CultuurNet\UDB3\Offer\OfferRepository;

class UpdateStatusHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof UpdateStatus)) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->updateAllStatuses($command->getStatus());
        $this->offerRepository->save($offer);
    }
}
