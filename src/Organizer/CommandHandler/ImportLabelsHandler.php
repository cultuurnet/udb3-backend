<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label as Udb3ModelLabel;
use CultuurNet\UDB3\Organizer\Commands\ImportLabels;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class ImportLabelsHandler implements CommandHandler
{
    /**
     * @var OrganizerRepository
     */
    private $organizerRepository;

    /**
     * @var LabelServiceInterface
     */
    private $labelService;

    public function __construct(OrganizerRepository $organizerRepository, LabelServiceInterface $labelService)
    {
        $this->organizerRepository = $organizerRepository;
        $this->labelService = $labelService;
    }

    public function handle($command): void
    {
        if (!($command instanceof ImportLabels)) {
            return;
        }

        /** @var Udb3ModelLabel $importLabel */
        foreach ($command->getLabels() as $importLabel) {
            $this->labelService->createLabelAggregateIfNew(
                new LabelName($importLabel->getName()->toString()),
                $importLabel->isVisible()
            );
        }

        $organizer = $this->organizerRepository->load($command->getOrganizerId());
        $organizer->importLabels($command->getLabels(), $command->getLabelsToKeepIfAlreadyOnOrganizer());
        $this->organizerRepository->save($organizer);
    }
}
