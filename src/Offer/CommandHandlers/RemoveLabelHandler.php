<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\RemoveLabel;
use CultuurNet\UDB3\Offer\OfferRepository;

final class RemoveLabelHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof RemoveLabel)) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->removeLabel($command->getLabelName());
        $this->offerRepository->save($offer);
    }
}
