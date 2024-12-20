<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Organizer\Commands\AddLabel;
use CultuurNet\UDB3\Organizer\OrganizerRepository;

final class AddLabelHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;

    private ReadRepositoryInterface $labelRepository;

    private LabelServiceInterface $labelService;

    public function __construct(
        OrganizerRepository $organizerRepository,
        ReadRepositoryInterface $labelRepository,
        LabelServiceInterface $labelService
    ) {
        $this->organizerRepository = $organizerRepository;
        $this->labelRepository = $labelRepository;
        $this->labelService = $labelService;
    }

    public function handle($command): void
    {
        if (!($command instanceof AddLabel)) {
            return;
        }

        $label = $command->getLabel();
        $name = $label->getName();
        $visible = $label->isVisible();

        $this->labelService->createLabelAggregateIfNew($name, $visible);

        $readModelLabelEntity = $this->labelRepository->getByName($name->toString());
        if ($readModelLabelEntity) {
            $visible = $readModelLabelEntity->getVisibility()->sameAs(Visibility::visible());
        }

        $labelWithCorrectVisibility = new Label($name, $visible);

        $organizer = $this->organizerRepository->load($command->getItemId());
        $organizer->addLabel($labelWithCorrectVisibility);
        $this->organizerRepository->save($organizer);
    }
}
