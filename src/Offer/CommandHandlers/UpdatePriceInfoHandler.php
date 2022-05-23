<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\PriceInfo\PriceInfo;

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
        $offer->updatePriceInfo(PriceInfo::fromUdb3ModelPriceInfo($command->getPriceInfo()));
        $this->offerRepository->save($offer);
    }
}
