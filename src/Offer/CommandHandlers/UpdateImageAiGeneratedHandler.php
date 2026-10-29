<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\Image\UpdateImageAiGenerated;
use CultuurNet\UDB3\Offer\OfferRepository;

final class UpdateImageAiGeneratedHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof UpdateImageAiGenerated)) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->updateImageAiGenerated($command->getMediaObjectId(), $command->isAiGenerated());
        $this->offerRepository->save($offer);
    }
}
