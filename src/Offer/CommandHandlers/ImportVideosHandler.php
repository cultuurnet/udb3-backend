<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\Video\ImportVideos;
use CultuurNet\UDB3\Offer\OfferRepository;

final class ImportVideosHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof ImportVideos)) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->importVideos($command->getVideos());
        $this->offerRepository->save($offer);
    }
}
