<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\UpdateBookingAvailability;
use CultuurNet\UDB3\Offer\OfferRepository;

class UpdateBookingAvailabilityHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateBookingAvailability) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->updateBookingAvailability($command->getBookingAvailability());
        $this->offerRepository->save($offer);
    }
}
