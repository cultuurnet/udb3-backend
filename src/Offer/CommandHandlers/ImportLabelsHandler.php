<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use CultuurNet\UDB3\Offer\OfferRepository;

final class ImportLabelsHandler implements CommandHandler
{
    /**
     * @var OfferRepository
     */
    private $offerRepository;

    /**
     * @var LabelServiceInterface
     */
    private $labelService;

    public function __construct(
        OfferRepository $offerRepository,
        LabelServiceInterface $labelService
    ) {
        $this->offerRepository = $offerRepository;
        $this->labelService = $labelService;
    }

    public function handle($command): void
    {
        if (!($command instanceof ImportLabels)) {
            return;
        }

        /** @var \CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label $importLabel */
        foreach ($command->getLabelsToImport() as $importLabel) {
            $this->labelService->createLabelAggregateIfNew(
                new LabelName($importLabel->getName()->toString()),
                $importLabel->isVisible()
            );
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->importLabels(
            $command->getLabelsToImport(),
            $command->getLabelsToKeepIfAlreadyOnOffer(),
            $command->getLabelsToRemoveWhenOnOffer()
        );
        $this->offerRepository->save($offer);
    }
}
