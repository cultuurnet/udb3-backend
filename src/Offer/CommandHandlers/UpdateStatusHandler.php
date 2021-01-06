<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandlerInterface;
use CultuurNet\UDB3\Offer\Commands\Status\UpdateStatus;
use CultuurNet\UDB3\Offer\OfferRepository;

class UpdateStatusHandler implements CommandHandlerInterface
{
    /**
     * @var OfferRepository
     */
    private $offerRepository;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof UpdateStatus)) {
            return;
        }

        $offer = $this->offerRepository->load($command->getItemId());
        $offer->updateStatus($command->getStatus());
        $this->offerRepository->save($offer);
    }
}
