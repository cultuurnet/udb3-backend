<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Label as LegacyLabel;
use CultuurNet\UDB3\Label\LabelServiceInterface;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Label\ValueObjects\LabelName as LegacyLabelName;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Offer\Commands\AddLabel;
use CultuurNet\UDB3\Offer\OfferRepository;
use ValueObjects\StringLiteral\StringLiteral;

final class AddLabelHandler implements CommandHandler
{
    /**
     * @var OfferRepository
     */
    private $offerRepository;

    /**
     * @var LabelServiceInterface
     */
    private $labelService;

    /**
     * @var ReadRepositoryInterface
     */
    private $labelRepository;

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
        if ($labelEntity instanceof LegacyLabel\ReadModels\JSON\Repository\Entity) {
            $labelVisibility = $labelEntity->getVisibility() === Visibility::VISIBLE();
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
