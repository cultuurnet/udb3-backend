<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Offer\OfferRepository;

class UpdatePriceInfoHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof UpdatePriceInfo)) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->updatePriceInfo($command->getPriceInfo());
        $this->offerRepository->save($offer);
    }
}
