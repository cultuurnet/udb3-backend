<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\Video\UpdateVideo;
use CultuurNet\UDB3\Offer\OfferRepository;

class UpdateVideoHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof UpdateVideo)) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->updateVideo(
            $command->getVideoId(),
            $command->getUrl(),
            $command->getLanguage(),
            $command->getCopyrightHolder()
        );
        $this->offerRepository->save($offer);
    }
}
