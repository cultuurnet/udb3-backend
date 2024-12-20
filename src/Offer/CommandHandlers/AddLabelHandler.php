<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\Entity;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use CultuurNet\UDB3\Offer\OfferRepository;

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
            new LabelName($command->getLabel()->getName()->toString()),
            $command->getLabel()->isVisible()
        );

        $labelName = $command->getLabel()->getName()->toString();
        $labelVisibility = $command->getLabel()->isVisible();

        // Load the label read model so we can determine the correct visibility.
        $labelEntity = $this->labelRepository->getByName($labelName);
        if ($labelEntity instanceof Entity) {
            $labelVisibility = $labelEntity->getVisibility()->sameAs(Visibility::visible());
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
