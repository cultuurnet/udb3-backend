<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\Video\AddVideo;
use CultuurNet\UDB3\Offer\OfferRepository;

final class AddVideoHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof AddVideo)) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->addVideo($command->getVideo());
        $this->offerRepository->save($offer);
    }
}
