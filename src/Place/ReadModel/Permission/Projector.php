<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\Permission;

use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\CreatedByToUserIdResolverInterface;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Security\ResourceOwner\ResourceOwnerRepository;
use CultuurNet\UDB3\Place\Events\OwnerChanged;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\StringLiteral;

class Projector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait;

    /**
     * @var CreatedByToUserIdResolverInterface
     */
    private $userIdResolver;

    /**
     * @var ResourceOwnerRepository
     */
    private $permissionRepository;

    public function __construct(
        ResourceOwnerRepository $permissionRepository,
        CreatedByToUserIdResolverInterface $createdByToUserIdResolver
    ) {
        $this->userIdResolver = $createdByToUserIdResolver;
        $this->permissionRepository = $permissionRepository;
    }

    protected function applyPlaceImportedFromUDB2(
        PlaceImportedFromUDB2 $placeImportedFromUDB2
    ) {
        $cdbActor = ActorItemFactory::createActorFromCdbXml(
            $placeImportedFromUDB2->getCdbXmlNamespaceUri(),
            $placeImportedFromUDB2->getCdbXml()
        );

        $createdByIdentifier = $cdbActor->getCreatedBy();

        if ($createdByIdentifier) {
            $ownerId = $this->userIdResolver->resolveCreatedByToUserId(
                new StringLiteral($createdByIdentifier)
            );

            if (!$ownerId) {
                return;
            }

            $this->permissionRepository->markResourceEditableByUser(
                new StringLiteral($placeImportedFromUDB2->getActorId()),
                $ownerId
            );
        }
    }

    protected function applyPlaceCreated(
        PlaceCreated $placeCreated,
        DomainMessage $domainMessage
    ) {
        $metadata = $domainMessage->getMetadata()->serialize();
        $ownerId = new StringLiteral($metadata['user_id']);

        $this->permissionRepository->markResourceEditableByUser(
            new StringLiteral($placeCreated->getPlaceId()),
            $ownerId
        );
    }

    protected function applyOwnerChanged(OwnerChanged $ownerChanged): void
    {
        $this->permissionRepository->markResourceEditableByNewUser(
            new StringLiteral($ownerChanged->getOfferId()),
            new StringLiteral($ownerChanged->getNewOwnerId())
        );
    }
}
