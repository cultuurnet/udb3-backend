<?php

namespace CultuurNet\UDB3\Organizer\ReadModel\Permission;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\CreatedByToUserIdResolverInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Offer\ReadModel\Permission\PermissionRepositoryInterface;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
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

    protected function applyOrganizerImportedFromUDB2(
        OrganizerImportedFromUDB2 $organizerImportedFromUDB2
    ) {
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

            $this->permissionRepository->markOfferEditableByUser(
                new StringLiteral($organizerImportedFromUDB2->getActorId()),
                $ownerId
            );
        }
    }

    protected function applyOrganizerCreated(
        OrganizerCreated $organizerCreated,
        DomainMessage $domainMessage
    ) {
        $this->makeOrganizerEditableByUser(
            $organizerCreated->getOrganizerId(),
            $domainMessage
        );
    }

    protected function applyOrganizerCreatedWithUniqueWebsite(
        OrganizerCreatedWithUniqueWebsite $organizerCreatedWithUniqueWebsite,
        DomainMessage $domainMessage
    ) {
        $this->makeOrganizerEditableByUser(
            $organizerCreatedWithUniqueWebsite->getOrganizerId(),
            $domainMessage
        );
    }

    /**
     * @param string $organizerId
     * @param DomainMessage $domainMessage
     */
    private function makeOrganizerEditableByUser(
        string $organizerId,
        DomainMessage $domainMessage
    ) {
        $metadata = $domainMessage->getMetadata()->serialize();
        $ownerId = new StringLiteral($metadata['user_id']);

        $this->permissionRepository->markOfferEditableByUser(
            new StringLiteral($organizerId),
            $ownerId
        );
    }
}
