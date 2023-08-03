<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\EventStore\EventStreamNotFoundException;
use CultuurNet\UDB3\Offer\Commands\DeleteDescription;
use CultuurNet\UDB3\Offer\OfferRepository;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

final class DeleteDescriptionHandler implements CommandHandler
{
    use LoggerAwareTrait;

    private OfferRepository $offerRepository;

    public function __construct(OfferRepository $offerRepository)
    {
        $this->offerRepository = $offerRepository;
        $this->logger = new NullLogger();
    }

    public function handle($command): void
    {
        if (!$command instanceof DeleteDescription) {
            return;
        }

        try {
            $offer = $this->offerRepository->load($command->getItemId());
        } catch (EventStreamNotFoundException $e) {
            $this->logger->debug(sprintf('Failed to delete description: %s', $e->getMessage()));
            return;
        }

        $offer->deleteDescription($command->getLanguage());
        $this->offerRepository->save($offer);
    }
}
