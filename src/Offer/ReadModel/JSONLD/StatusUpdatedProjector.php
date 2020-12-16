<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Offer\Events\StatusUpdated;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use Psr\Log\LoggerInterface;

final class StatusUpdatedProjector implements EventListenerInterface
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var DocumentRepository
     */
    private $eventRepository;

    /**
     * @var DocumentRepository
     */
    private $placeRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        DocumentRepository $eventRepository,
        DocumentRepository $placeRepository,
        LoggerInterface $logger
    ) {
        $this->eventRepository = $eventRepository;
        $this->placeRepository = $placeRepository;
        $this->logger = $logger;
    }

    protected function applyStatusUpdated(StatusUpdated $statusUpdated): void
    {
        $offer = null;
        $offerId = $statusUpdated->getId();

        try {
            $offer = $this->eventRepository->fetch($offerId);
        } catch (DocumentDoesNotExist $documentDoesNotExist) {
            $this->logger->debug('No event found with id ' . $offerId . ' to apply StatusUpdated.');
        }

        if ($offer) {
            // Update the event status and sub events.

            $this->logger->debug('Applied StatusUpdated on event with id ' . $offerId);
            return;
        }

        try {
            $offer = $this->placeRepository->fetch($offerId);
        } catch (DocumentDoesNotExist $documentDoesNotExist) {
            $this->logger->warning('No place or event found with id ' . $offerId . ' to apply StatusUpdated.');
            return;
        }

        // Update the place status.
        $this->logger->debug('Applied StatusUpdated on place with id ' . $offerId);
    }
}
