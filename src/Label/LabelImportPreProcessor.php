<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsPermissionRepository;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;

final class LabelImportPreProcessor
{
    private LabelServiceInterface $labelService;
    private LabelsPermissionRepository $labelsPermissionRepository;
    private string $currentUserId;

    public function __construct(
        LabelServiceInterface $labelService,
        LabelsPermissionRepository $labelsPermissionRepository,
        string $currentUserId
    ) {
        $this->labelService = $labelService;
        $this->labelsPermissionRepository = $labelsPermissionRepository;
        $this->currentUserId = $currentUserId;
    }

    public function preProcessImportLabels(Labels $importLabels, Labels $labelsAlreadyOnResource): Labels
    {
        $importLabels = $this->fixVisibility($importLabels);
        $labelsAlreadyOnResource = $this->fixVisibility($labelsAlreadyOnResource);

        // Always keep labels that the user has no permission to remove, whether they are included in the import or not.
        // Do not throw an exception but just keep them, because the user might not have had up-to-date JSON from UDB
        // with the extra labels when they sent their import.
        /** @var Label $labelOnOffer */
        foreach ($labelsAlreadyOnResource as $labelOnOffer) {
            $labelName = $labelOnOffer->getName()->toString();
            $canUseLabel = $this->labelsPermissionRepository->canUseLabel(
                $this->currentUserId,
                $labelName
            );
            if (!$canUseLabel && !$importLabels->contains($labelOnOffer)) {
                $importLabels = $importLabels->with($labelOnOffer);
            }
        }

        // Loop over the labels that are to be added and check if the user can use them or not. If they cannot use them
        // just remove them from the list to import, because at this point we cannot return an error response anymore
        // because the imports are handled async. Normally the AuthorizedCommandBus should have already returned an
        // error response for this, but if not make sure we don't add them.
        $labelNamesToImport = $importLabels->toArrayOfStringNames();
        $labelNamesOnOffer = $labelsAlreadyOnResource->toArrayOfStringNames();
        $labelNamesNotOnOffer = array_diff($labelNamesToImport, $labelNamesOnOffer);
        foreach ($labelNamesNotOnOffer as $labelName) {
            $canUseLabel = $this->labelsPermissionRepository->canUseLabel(
                $this->currentUserId,
                $labelName
            );

            if (!$canUseLabel) {
                $importLabels = $importLabels->filter(
                    fn (Label $label) => $label->getName()->toString() !== $labelName
                );
            }
        }

        // Make sure every label that will get added has an existing Label aggregate.
        /** @var Label $importLabel */
        foreach ($importLabels as $importLabel) {
            $this->labelService->createLabelAggregateIfNew(
                $importLabel->getName(),
                $importLabel->isVisible()
            );
        }

        return $importLabels;
    }

    private function fixVisibility(Labels $labels): Labels
    {
        return new Labels(
            ...array_map(
                function (Label $label): Label {
                    $readModel = $this->labelsPermissionRepository->getByName(
                        $label->getName()->toString()
                    );
                    $visible = !$readModel ?
                        $label->isVisible() : $readModel->getVisibility()->sameAs(Visibility::visible());
                    return new Label($label->getName(), $visible);
                },
                $labels->toArray()
            )
        );
    }
}
