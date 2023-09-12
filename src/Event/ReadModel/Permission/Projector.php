<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\Permission;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Cdb\CreatedByToUserIdResolverInterface;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\OwnerChanged;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerRepository;
use CultuurNet\UDB3\StringLiteral;

class Projector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    private CreatedByToUserIdResolverInterface $userIdResolver;

    private ResourceOwnerRepository $permissionRepository;

    public function __construct(
        ResourceOwnerRepository $permissionRepository,
        CreatedByToUserIdResolverInterface $createdByToUserIdResolver
    ) {
        $this->userIdResolver = $createdByToUserIdResolver;
        $this->permissionRepository = $permissionRepository;
    }

    protected function applyEventImportedFromUDB2(
        EventImportedFromUDB2 $eventImportedFromUDB2
    ): void {
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

            $this->permissionRepository->markResourceEditableByUser(
                $eventImportedFromUDB2->getEventId(),
                $ownerId->toNative()
            );
        }
    }

    protected function applyEventCreated(
        EventCreated $eventCreated,
        DomainMessage $domainMessage
    ): void {
        $this->makeOfferEditableByUser($eventCreated->getEventId(), $domainMessage);
    }

    protected function applyEventCopied(
        EventCopied $eventCopied,
        DomainMessage $domainMessage
    ): void {
        $this->makeOfferEditableByUser($eventCopied->getItemId(), $domainMessage);
    }

    protected function applyOwnerChanged(OwnerChanged $ownerChanged): void
    {
        $this->permissionRepository->markResourceEditableByNewUser(
            $ownerChanged->getOfferId(),
            $ownerChanged->getNewOwnerId()
        );
    }

    private function makeOfferEditableByUser(
        string $offerId,
        DomainMessage $domainMessage
    ): void {
        $metadata = $domainMessage->getMetadata()->serialize();
        $ownerId = $metadata['user_id'];

        $this->permissionRepository->markResourceEditableByUser(
            $offerId,
            $ownerId
        );
    }
}
