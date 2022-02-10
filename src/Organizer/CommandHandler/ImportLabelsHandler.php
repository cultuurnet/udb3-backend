<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Http\ApiProblem\ApiProblem;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface as LabelsPermissionRepository;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as LegacyLabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use ValueObjects\StringLiteral\StringLiteral;

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

        $labelsOnOrganizer = $organizer->getLabels()->toArray();

        $labelNamesOnOrganizer = array_map(
            fn (Label $label) => $label->getName()->toString(),
            $labelsOnOrganizer
        );

        $labelNamesNotOnOrganizer = array_diff(
            array_map(
                fn (StringLiteral $labelName) => $labelName->toNative(),
                $command->getLabelNames()
            ),
            $labelNamesOnOrganizer
        );

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
        foreach ($command->getLabels() as $importLabel) {
            $this->labelService->createLabelAggregateIfNew(
                new LegacyLabelName($importLabel->getName()->toString()),
                $importLabel->isVisible()
            );
        }

        $organizer->importLabels($command->getLabels(), $labelsToKeepOnOrganizer);
        $this->organizerRepository->save($organizer);
    }
}
