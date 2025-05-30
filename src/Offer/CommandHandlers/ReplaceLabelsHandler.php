<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Label\LabelImportPreProcessor;
use CultuurNet\UDB3\Offer\Commands\ReplaceLabels;
use CultuurNet\UDB3\Offer\OfferRepository;

final class ReplaceLabelsHandler implements CommandHandler
{
    private OfferRepository $offerRepository;
    private LabelImportPreProcessor $labelImportPreProcessor;

    public function __construct(
        OfferRepository $offerRepository,
        LabelImportPreProcessor $labelImportPreProcessor
    ) {
        $this->offerRepository = $offerRepository;
        $this->labelImportPreProcessor = $labelImportPreProcessor;
    }

    public function handle($command): void
    {
        if (!($command instanceof ReplaceLabels)) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());

        $labels = $this->labelImportPreProcessor->preProcessImportLabels($command->getLabels(), $offer->getLabels());
        $offer->replaceLabels($labels);

        $this->offerRepository->save($offer);
    }
}
