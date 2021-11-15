<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\UpdateAvailableFrom;
use CultuurNet\UDB3\Offer\OfferRepository;

final class UpdateAvailableFromHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateAvailableFrom) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->updateAvailableFrom($command->getAvailableFrom());
        $this->offerRepository->save($offer);
    }
}
