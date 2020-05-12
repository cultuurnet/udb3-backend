<?php

namespace CultuurNet\UDB3\Event\ReadModel\Permission;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Cdb\CreatedByToUserIdResolverInterface;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Offer\ReadModel\Permission\PermissionRepositoryInterface;
use ValueObjects\StringLiteral\StringLiteral;

class Projector implements EventListenerInterface
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var CreatedByToUserIdResolverInterface
     */
    private $userIdResolver;

    /**
     * @var PermissionRepositoryInterface
     */
    private $permissionRepository;

    public function __construct(
        PermissionRepositoryInterface $permissionRepository,
        CreatedByToUserIdResolverInterface $createdByToUserIdResolver
    ) {
        $this->userIdResolver = $createdByToUserIdResolver;
        $this->permissionRepository = $permissionRepository;
    }

    protected function applyEventImportedFromUDB2(
        EventImportedFromUDB2 $eventImportedFromUDB2
    ) {
        $cdbEvent = EventItemFactory::createEventFromCdbXml(
            $eventImportedFromUDB2->getCdbXmlNamespaceUri(),
            $eventImportedFromUDB2->getCdbXml()
        );

        $createdByIdentifier = $cdbEvent->getCreatedBy();

        if ($createdByIdentifier) {
            $ownerId = $this->userIdResolver->resolveCreatedByToUserId(
                new StringLiteral($createdByIdentifier)
            );

            if (!$ownerId) {
                return;
            }

            $this->permissionRepository->markOfferEditableByUser(
                new StringLiteral($eventImportedFromUDB2->getEventId()),
                $ownerId
            );
        }
    }

    protected function applyEventCreated(
        EventCreated $eventCreated,
        DomainMessage $domainMessage
    ) {
        $this->makeOfferEditableByUser($eventCreated->getEventId(), $domainMessage);
    }

    protected function applyEventCopied(
        EventCopied $eventCopied,
        DomainMessage $domainMessage
    ) {
        $this->makeOfferEditableByUser($eventCopied->getItemId(), $domainMessage);
    }

    /**
     * @param string $offerId
     * @param DomainMessage $domainMessage
     */
    private function makeOfferEditableByUser(
        $offerId,
        DomainMessage $domainMessage
    ) {
        $metadata = $domainMessage->getMetadata()->serialize();
        $ownerId = new StringLiteral($metadata['user_id']);

        $this->permissionRepository->markOfferEditableByUser(
            new StringLiteral($offerId),
            $ownerId
        );
    }
}
