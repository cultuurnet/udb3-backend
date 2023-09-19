<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Commands\UpdateTitle;
use CultuurNet\UDB3\Offer\OfferRepository;

final class UpdateTitleHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateTitle) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());

        $offer->updateTitle(
            Language::fromUdb3ModelLanguage($command->getLanguage()),
            $command->getTitle()
        );

        $this->offerRepository->save($offer);
    }
}
