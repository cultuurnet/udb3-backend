<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsPermissionRepository;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as LegacyLabelName;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\Import\Taxonomy\Label\LockedLabelRepository;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use ValueObjects\StringLiteral\StringLiteral;

final class ImportLabelsHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;
    private LabelServiceInterface $labelService;
    private LabelsPermissionRepository $labelsPermissionRepository;
    private LockedLabelRepository $lockedLabelRepository;
    private string $currentUserId;

    public function __construct(
        OrganizerRepository $organizerRepository,
        LabelServiceInterface $labelService,
        LabelsPermissionRepository $labelsPermissionRepository,
        LockedLabelRepository $lockedLabelRepository,
        string $currentUserId
    ) {
        $this->organizerRepository = $organizerRepository;
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

        $organizer = $this->organizerRepository->load($command->getItemId());

        $labelsToImport = $command->getLabels();
        $labelNamesToImport = array_map(
            fn (Label $label) => $label->getName()->toString(),
            $labelsToImport->toArray()
        );

        $labelsOnOrganizer = $organizer->getLabels();
        $labelNamesOnOrganizer = array_map(
            fn (Label $label) => $label->getName()->toString(),
            $labelsOnOrganizer->toArray()
        );

        $labelsToKeepOnOrganizer = $this->lockedLabelRepository->getLockedLabelsForItem($command->getItemId());

        // Fix visibility that is sometimes incorrect on the labels to keep according to the command. This otherwise
        // breaks comparisons in logic down the line.
        $labelsToKeepOnOrganizer = new Labels(
            ...array_map(
                function (Label $label): Label {
                    $readModel = $this->labelsPermissionRepository->getByName(
                        new StringLiteral($label->getName()->toString())
                    );
                    $visible = !$readModel || $readModel->getVisibility()->sameValueAs(Visibility::VISIBLE());
                    return new Label($label->getName(), $visible);
                },
                $labelsToKeepOnOrganizer->toArray()
            )
        );

        /** @var Label $labelOnOrganizer */
        foreach ($labelsOnOrganizer as $labelOnOrganizer) {
            $canUseLabel = $this->labelsPermissionRepository->canUseLabel(
                new StringLiteral($this->currentUserId),
                new StringLiteral($labelOnOrganizer->getName()->toString())
            );
            if (!$canUseLabel && !$labelsToKeepOnOrganizer->contains($labelOnOrganizer)) {
                // Always keep labels that are not included in the import and the user does not have permission to
                // remove them. Just keep them but don't throw an exception, because it can be an importer who did not
                // fetch the latest labels from the organizer in UDB before sending their data and they didn't mean to
                // remove these.
                $labelsToKeepOnOrganizer = $labelsToKeepOnOrganizer->with($labelOnOrganizer);
            }
        }

        $labelNamesNotOnOrganizer = array_diff($labelNamesToImport, $labelNamesOnOrganizer);

        foreach ($labelNamesNotOnOrganizer as $labelName) {
            $canUseLabel = $this->labelsPermissionRepository->canUseLabel(
                new StringLiteral($this->currentUserId),
                new StringLiteral($labelName)
            );
            if (!$canUseLabel) {
                throw ApiProblem::labelNotAllowed($labelName);
            }
        }

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
}
