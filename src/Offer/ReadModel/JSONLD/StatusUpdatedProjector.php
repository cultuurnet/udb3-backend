<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Offer\Events\StatusUpdated;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\OfferDocumentRepository;
use Psr\Log\LoggerInterface;

final class StatusUpdatedProjector implements EventListenerInterface
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var OfferDocumentRepository
     */
    private $offerRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        OfferDocumentRepository $offerRepository,
        LoggerInterface $logger
    ) {
        $this->offerRepository = $offerRepository;
        $this->logger = $logger;
    }

    protected function applyStatusUpdated(StatusUpdated $statusUpdated): void
    {
        try {
            $this->updateStatus($this->offerRepository, $statusUpdated);
            $this->logger->debug('Applied StatusUpdated on offer with id ' . $statusUpdated->getId());
            return;
        } catch (DocumentDoesNotExist $documentDoesNotExist) {
            $this->logger->warning('No offer found with id ' . $statusUpdated->getId() . ' to apply StatusUpdated.');
        }
    }

    /**
     * @throws DocumentDoesNotExist
     */
    private function updateStatus(OfferDocumentRepository $offerRepository, StatusUpdated $statusUpdated): void
    {
        $jsonDocument = $offerRepository->fetch($statusUpdated->getId());
        $json = $jsonDocument->getAssocBody();

        $json['status'] = $statusUpdated->getStatus()->serialize();

        if (!empty($json['subEvent'])) {
            $nrOfSubEvents = count($json['subEvent']);
            for ($subEventIndex = 0; $subEventIndex < $nrOfSubEvents; $subEventIndex++) {
                $json['subEvent'][$subEventIndex]['status'] = $statusUpdated->getStatus()->serialize();
            }
        }

        $offerRepository->save($jsonDocument->withAssocBody($json));
    }
}
