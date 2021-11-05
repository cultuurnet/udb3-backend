<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use CultuurNet\UDB3\Offer\OfferRepository;

final class DeleteOfferHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof DeleteOffer) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->delete();
        $this->offerRepository->save($offer);
    }
}
