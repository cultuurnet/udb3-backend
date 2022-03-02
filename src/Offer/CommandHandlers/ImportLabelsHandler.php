<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsPermissionRepository;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as LegacyLabelName;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\Import\Taxonomy\Label\LockedLabelRepository;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Offer\Commands\ImportLabels;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\StringLiteral;

final class ImportLabelsHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    private LabelServiceInterface $labelService;

    private LabelsPermissionRepository $labelsPermissionRepository;

    private LockedLabelRepository $lockedLabelRepository;

    private string $currentUserId;

    public function __construct(
        OfferRepository $offerRepository,
        LabelServiceInterface $labelService,
        LabelsPermissionRepository $labelsPermissionRepository,
        LockedLabelRepository $lockedLabelRepository,
        string $currentUserId
    ) {
        $this->offerRepository = $offerRepository;
        $this->labelService = $labelService;
        $this->labelsPermissionRepository = $labelsPermissionRepository;
        $this->lockedLabelRepository = $lockedLabelRepository;
        $this->currentUserId = $currentUserId;
    }

    public function handle($command): void
    {
        if (!($command instanceof ImportLabels)) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());

        $labelsToImport = $command->getLabels();
        $labelsToImport = $this->fixVisibility($labelsToImport);

        $labelsOnOffer = new Labels();
        foreach ($offer->getLabels()->asArray() as $labelOnOffer) {
            $labelsOnOffer = $labelsOnOffer->with(
                new Label(
                    new LabelName($labelOnOffer->getName()->toNative()),
                    $labelOnOffer->isVisible()
                )
            );
        }

        $labelsToKeepOnOffer = $this->lockedLabelRepository->getLockedLabelsForItem($command->getItemId());

        // Fix visibility that is sometimes incorrect on locked labels. This otherwise breaks comparisons in logic down
        // the line.
        $labelsToKeepOnOffer = $this->fixVisibility($labelsToKeepOnOffer);

        // Always keep labels that the user has no permission to remove, whether they are included in the import or not.
        // Do not throw an exception but just keep them, because the user might not have had up-to-date JSON from UDB
        // with the extra labels when they sent their import.
        /** @var Label $labelOnOffer */
        foreach ($labelsOnOffer as $labelOnOffer) {
            $canUseLabel = $this->labelsPermissionRepository->canUseLabel(
                new StringLiteral($this->currentUserId),
                new StringLiteral($labelOnOffer->getName()->toString())
            );
            if (!$canUseLabel && !$labelsToKeepOnOffer->contains($labelOnOffer)) {
                $labelsToKeepOnOffer = $labelsToKeepOnOffer->with($labelOnOffer);
            }
        }

        // Loop over the labels that are to be added and check if the user can use them or not. If they cannot use them
        // just remove them from the list to import, because at this point we cannot return an error response anymore
        // because the imports are handled async. Normally the AuthorizedCommandBus should have already returned an
        // error response for this, but if not make sure we don't add them.
        $labelNamesToImport = $labelsToImport->toArrayOfStringNames();
        $labelNamesOnOffer = $labelsOnOffer->toArrayOfStringNames();
        $labelNamesNotOnOffer = array_diff($labelNamesToImport, $labelNamesOnOffer);
        foreach ($labelNamesNotOnOffer as $labelName) {
            $canUseLabel = $this->labelsPermissionRepository->canUseLabel(
                new StringLiteral($this->currentUserId),
                new StringLiteral($labelName)
            );

            if (!$canUseLabel) {
                $labelsToImport = $labelsToImport->filter(
                    fn (Label $label) => $label->getName()->toString() !== $labelName
                );
            }
        }

        // Make sure every label that will get added has an existing Label aggregate.
        /** @var Label $importLabel */
        foreach ($labelsToImport as $importLabel) {
            $this->labelService->createLabelAggregateIfNew(
                new LegacyLabelName($importLabel->getName()->toString()),
                $importLabel->isVisible()
            );
        }

        $offer->importLabels($labelsToImport, $labelsToKeepOnOffer);

        $this->offerRepository->save($offer);
    }

    private function fixVisibility(Labels $labels): Labels
    {
        return new Labels(
            ...array_map(
                function (Label $label): Label {
                    $readModel = $this->labelsPermissionRepository->getByName(
                        new StringLiteral($label->getName()->toString())
                    );
                    $visible = !$readModel || $readModel->getVisibility()->sameAs(Visibility::VISIBLE());
                    return new Label($label->getName(), $visible);
                },
                $labels->toArray()
            )
        );
    }
}
