<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\Permission;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\CreatedByToUserIdResolverInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Organizer\Events\OwnerChanged;
use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerRepository;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
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

    protected function applyOrganizerImportedFromUDB2(
        OrganizerImportedFromUDB2 $organizerImportedFromUDB2
    ): void {
        $cdbEvent = ActorItemFactory::createActorFromCdbXml(
            $organizerImportedFromUDB2->getCdbXmlNamespaceUri(),
            $organizerImportedFromUDB2->getCdbXml()
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
                new StringLiteral($organizerImportedFromUDB2->getActorId()),
                $ownerId
            );
        }
    }

    protected function applyOrganizerCreated(
        OrganizerCreated $organizerCreated,
        DomainMessage $domainMessage
    ): void {
        $this->makeOrganizerEditableByUser(
            $organizerCreated->getOrganizerId(),
            $domainMessage
        );
    }

    protected function applyOrganizerCreatedWithUniqueWebsite(
        OrganizerCreatedWithUniqueWebsite $organizerCreatedWithUniqueWebsite,
        DomainMessage $domainMessage
    ): void {
        $this->makeOrganizerEditableByUser(
            $organizerCreatedWithUniqueWebsite->getOrganizerId(),
            $domainMessage
        );
    }

    protected function applyOwnerChanged(OwnerChanged $ownerChanged): void
    {
        $this->permissionRepository->markResourceEditableByNewUser(
            new StringLiteral($ownerChanged->getOrganizerId()),
            new StringLiteral($ownerChanged->getNewOwnerId())
        );
    }

    private function makeOrganizerEditableByUser(
        string $organizerId,
        DomainMessage $domainMessage
    ): void {
        $metadata = $domainMessage->getMetadata()->serialize();
        $ownerId = new StringLiteral($metadata['user_id']);

        $this->permissionRepository->markResourceEditableByUser(
            new StringLiteral($organizerId),
            $ownerId
        );
    }
}
