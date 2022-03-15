<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Label\LabelImportPreProcessor;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use CultuurNet\UDB3\Offer\OfferRepository;

final class ImportLabelsHandler implements CommandHandler
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
        if (!($command instanceof ImportLabels)) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());

        $labels = $this->labelImportPreProcessor->preProcessImportLabels($command->getLabels(), $offer->getLabels());
        $offer->importLabels($labels);

        $this->offerRepository->save($offer);
    }
}
