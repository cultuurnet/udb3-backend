<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Label\LabelImportPreProcessor;
use CultuurNet\UDB3\Organizer\Commands\ReplaceLabels;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class ReplaceLabelsHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;
    private LabelImportPreProcessor $labelImportPreProcessor;

    public function __construct(
        OrganizerRepository $organizerRepository,
        LabelImportPreProcessor $labelImportPreProcessor
    ) {
        $this->organizerRepository = $organizerRepository;
        $this->labelImportPreProcessor = $labelImportPreProcessor;
    }

    public function handle($command): void
    {
        if (!($command instanceof ReplaceLabels)) {
            return;
        }

        $organizer = $this->organizerRepository->load($command->getItemId());

        $labels = $this->labelImportPreProcessor->preProcessImportLabels(
            $command->getLabels(),
            $organizer->getLabels()
        );
        $organizer->replaceLabels($labels);

        $this->organizerRepository->save($organizer);
    }
}
