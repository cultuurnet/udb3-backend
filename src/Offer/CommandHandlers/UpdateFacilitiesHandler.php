<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\AbstractUpdateFacilities;
use CultuurNet\UDB3\Offer\OfferRepository;

final class UpdateFacilitiesHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offer)
    {
        $this->offerRepository = $offer;
    }

    public function handle($command): void
    {
        if (!($command instanceof AbstractUpdateFacilities)) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->updateFacilities($command->getFacilities());
        $this->offerRepository->save($offer);
    }
}
