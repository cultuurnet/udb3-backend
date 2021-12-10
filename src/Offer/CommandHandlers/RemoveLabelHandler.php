<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Offer\Commands\RemoveLabel;
use CultuurNet\UDB3\Offer\OfferRepository;

final class RemoveLabelHandler implements CommandHandler
{
    /**
     * @var OfferRepository
     */
    private $offerRepository;

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
        $label = new Label(new LabelName($command->getLabel()->getName()->toNative()), $command->getLabel()->isVisible());
        $offer->removeLabel($label);
        $this->offerRepository->save($offer);
    }
}
