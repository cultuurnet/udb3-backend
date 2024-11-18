<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Role\ValueObjects\Permission;
use CultuurNet\UDB3\Security\Permission\CannotDeleteUiTPASPlace;
use CultuurNet\UDB3\Security\Permission\DeleteUiTPASPlaceVoter;

final class DeleteOfferHandler implements CommandHandler
{
    private OfferRepository $offerRepository;
    private DeleteUiTPASPlaceVoter $voter;
    private string $currentUserId;

    public function __construct(OfferRepository $offerRepository, DeleteUiTPASPlaceVoter $voter, string $currentUserId)
    {
        $this->offerRepository = $offerRepository;
        $this->voter = $voter;
        $this->currentUserId = $currentUserId;
    }

    /**
     * @throws CannotDeleteUiTPASPlace
     */
    public function handle($command): void
    {
        if (!$command instanceof DeleteOffer) {
            return;
        }

        if (! $this->voter->isAllowed(Permission::aanbodVerwijderen(), $command->getItemId(), $this->currentUserId)) {
            throw new CannotDeleteUiTPASPlace();
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->delete();
        $this->offerRepository->save($offer);
    }
}
