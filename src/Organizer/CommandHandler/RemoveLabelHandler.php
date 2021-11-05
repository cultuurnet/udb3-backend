<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\CommandHandler;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Organizer\Commands\RemoveLabel;
use CultuurNet\UDB3\Organizer\OrganizerRepository;
use ValueObjects\StringLiteral\StringLiteral;

final class RemoveLabelHandler implements CommandHandler
{
    private OrganizerRepository $organizerRepository;

    private ReadRepositoryInterface $labelRepository;

    public function __construct(OrganizerRepository $organizerRepository, ReadRepositoryInterface $labelRepository)
    {
        $this->organizerRepository = $organizerRepository;
        $this->labelRepository = $labelRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof RemoveLabel)) {
            return;
        }

        $labelName = new StringLiteral($command->getLabel()->getName()->toString());
        $visible = $command->getLabel()->isVisible();

        $readModelLabelEntity = $this->labelRepository->getByName($labelName);
        if ($readModelLabelEntity) {
            $visible = $readModelLabelEntity->getVisibility() === Visibility::VISIBLE();
        }

        $label = new Label($labelName->toNative(), $visible);

        $organizer = $this->organizerRepository->load($command->getItemId());
        $organizer->removeLabel($label);
        $this->organizerRepository->save($organizer);
    }
}
