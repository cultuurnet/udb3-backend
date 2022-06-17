<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\DeleteOrganizer;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\UiTPAS\Validation\EventHasTicketSalesGuard;

final class DeleteOrganizerHandler implements CommandHandler
{
    private OfferRepository $offerRepository;

    private EventHasTicketSalesGuard $organizerChangeAllowedBasedOnTicketSales;

    public function __construct(
        OfferRepository $offerRepository,
        EventHasTicketSalesGuard $organizerChangeAllowedBasedOnTicketSales
    ) {
        $this->offerRepository = $offerRepository;
        $this->organizerChangeAllowedBasedOnTicketSales = $organizerChangeAllowedBasedOnTicketSales;
    }

    public function handle($command): void
    {
        if (!($command instanceof DeleteOrganizer)) {
            return;
        }

        $this->organizerChangeAllowedBasedOnTicketSales->guard($command);

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->deleteOrganizer($command->getOrganizerId());
        $this->offerRepository->save($offer);
    }
}
