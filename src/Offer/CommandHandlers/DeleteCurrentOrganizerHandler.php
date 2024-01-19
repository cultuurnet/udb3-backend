<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Offer\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Offer\OfferRepository;
use CultuurNet\UDB3\UiTPAS\Validation\EventHasTicketSalesGuard;

final class DeleteCurrentOrganizerHandler implements CommandHandler
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
        if (!$command instanceof DeleteCurrentOrganizer) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());

        $this->organizerChangeAllowedBasedOnTicketSales->guard($command);

        $offer->deleteCurrentOrganizer();
        $this->offerRepository->save($offer);
    }
}
