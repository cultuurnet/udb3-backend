<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsPermissionRepository;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as LegacyLabelName;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use CultuurNet\UDB3\StringLiteral;

final class ImportLabelsHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;
    private LabelServiceInterface $labelService;
    private LabelsPermissionRepository $labelsPermissionRepository;
    private string $currentUserId;

    public function __construct(
        OrganizerRepository $organizerRepository,
        LabelServiceInterface $labelService,
        LabelsPermissionRepository $labelsPermissionRepository,
        string $currentUserId
    ) {
        $this->organizerRepository = $organizerRepository;
        $this->labelService = $labelService;
        $this->labelsPermissionRepository = $labelsPermissionRepository;
        $this->currentUserId = $currentUserId;
    }

    public function handle($command): void
    {
        if (!($command instanceof ImportLabels)) {
            return;
        }

        $organizer = $this->organizerRepository->load($command->getItemId());

        $labelsToImport = $this->fixVisibility($command->getLabels());
        $labelsOnOrganizer = $organizer->getLabels();
        $labelsToKeepOnOrganizer = new Labels();

        // Always keep labels that the user has no permission to remove, whether they are included in the import or not.
        // Do not throw an exception but just keep them, because the user might not have had up-to-date JSON from UDB
        // with the extra labels when they sent their import.
        /** @var Label $labelOnOrganizer */
        foreach ($labelsOnOrganizer as $labelOnOrganizer) {
            $labelName = $labelOnOrganizer->getName()->toString();
            $canUseLabel = $this->labelsPermissionRepository->canUseLabel(
                new StringLiteral($this->currentUserId),
                new StringLiteral($labelName)
            );
            if (!$canUseLabel && !in_array($labelName, $labelsToKeepOnOrganizer->toArrayOfStringNames(), true)) {
                $labelsToKeepOnOrganizer = $labelsToKeepOnOrganizer->with($labelOnOrganizer);
            }
        }

        // Loop over the labels that are to be added and check if the user can use them or not. If they cannot use them
        // just remove them from the list to import, because at this point we cannot return an error response anymore
        // because the imports are handled async. Normally the AuthorizedCommandBus should have already returned an
        // error response for this, but if not make sure we don't add them.
        $labelNamesToImport = $labelsToImport->toArrayOfStringNames();
        $labelNamesOnOrganizer = $labelsOnOrganizer->toArrayOfStringNames();
        $labelNamesNotOnOrganizer = array_diff($labelNamesToImport, $labelNamesOnOrganizer);
        foreach ($labelNamesNotOnOrganizer as $labelName) {
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

        $organizer->importLabels($labelsToImport, $labelsToKeepOnOrganizer);
        $this->organizerRepository->save($organizer);
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
