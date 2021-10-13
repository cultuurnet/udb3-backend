<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\Video\DeleteVideo;
use CultuurNet\UDB3\Offer\OfferRepository;

final class DeleteVideoHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof DeleteVideo) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->deleteVideo($command->getVideoId());
        $this->offerRepository->save($offer);
    }
}
