<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\ChangeOwner;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerQuery;

final class ChangeOwnerHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    private ResourceOwnerQuery $permissionQuery;

    public function __construct(
        OfferRepository $offerRepository,
        ResourceOwnerQuery $permissionQuery
    ) {
        $this->offerRepository = $offerRepository;
        $this->permissionQuery = $permissionQuery;
    }

    public function handle($command): void
    {
        if (!($command instanceof ChangeOwner)) {
            return;
        }

        $offerId = $command->getOfferId();
        $newOwnerId = $command->getNewOwnerId();

        // The aggregate cannot check who was the initial owner, because that's stored in the metadata (!) of the
        // EventCreated/PlaceCreated/EventImportedFromUDB2/PlaceImportedFromUDB2 events and the base class of broadway
        // doesn't pass that info to the applyEventCreated() etc methods.
        $offersOwnedByNewOwner = $this->permissionQuery->getEditableResourceIds($newOwnerId);

        if (!in_array($offerId, $offersOwnedByNewOwner)) {
            $offer = $this->offerRepository->load($command->getOfferId());
            $offer->changeOwner($command->getNewOwnerId());
            $this->offerRepository->save($offer);
        }
    }
}
