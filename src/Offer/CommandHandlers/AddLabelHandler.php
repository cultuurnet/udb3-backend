<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as LegacyLabelName;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\StringLiteral;

final class AddLabelHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    private LabelServiceInterface $labelService;

    private ReadRepositoryInterface $labelRepository;

    public function __construct(
        OfferRepository $offerRepository,
        LabelServiceInterface $labelService,
        ReadRepositoryInterface $labelRepository
    ) {
        $this->offerRepository = $offerRepository;
        $this->labelService = $labelService;
        $this->labelRepository = $labelRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof AddLabel)) {
            return;
        }

        $this->labelService->createLabelAggregateIfNew(
            new LegacyLabelName((string) $command->getLabel()),
            $command->getLabel()->isVisible()
        );

        $labelName = (string) $command->getLabel();
        $labelVisibility = $command->getLabel()->isVisible();

        // Load the label read model so we can determine the correct visibility.
        $labelEntity = $this->labelRepository->getByName(new StringLiteral($labelName));
        if ($labelEntity instanceof Entity) {
            $labelVisibility = $labelEntity->getVisibility()->sameAs(Visibility::VISIBLE());
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->addLabel(
            new Label(
                new LabelName($labelName),
                $labelVisibility
            )
        );
        $this->offerRepository->save($offer);
    }
}
