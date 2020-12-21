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
        try {
            $this->updateStatus($this->eventRepository, $statusUpdated);
            $this->logger->debug('Applied StatusUpdated on event with id ' . $statusUpdated->getId());
            return;
        } catch (DocumentDoesNotExist $documentDoesNotExist) {
            $this->logger->debug('No event found with id ' . $statusUpdated->getId() . ' to apply StatusUpdated.');
        }

        try {
            $this->updateStatus($this->placeRepository, $statusUpdated);
            $this->logger->debug('Applied StatusUpdated on place with id ' . $statusUpdated->getId());
            return;
        } catch (DocumentDoesNotExist $documentDoesNotExist) {
            $this->logger->warning('No place or event found with id ' . $statusUpdated->getId() . ' to apply StatusUpdated.');
        }
    }

    /**
     * @throws DocumentDoesNotExist
     */
    private function updateStatus(DocumentRepository $documentRepository, StatusUpdated $statusUpdated): void
    {
        $jsonDocument = $documentRepository->fetch($statusUpdated->getId());
        $json = $jsonDocument->getAssocBody();

        $json['status'] = $statusUpdated->getStatus()->serialize();

        if (!empty($json['subEvent'])) {
            $nrOfSubEvents = count($json['subEvent']);
            for ($subEventIndex = 0; $subEventIndex < $nrOfSubEvents; $subEventIndex++) {
                $json['subEvent'][$subEventIndex]['status'] = $statusUpdated->getStatus()->serialize();
            }
        }

        $documentRepository->save($jsonDocument->withAssocBody($json));
    }
}
