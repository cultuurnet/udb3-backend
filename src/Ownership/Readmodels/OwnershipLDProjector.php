<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\DateTimeFactory;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipDeleted;
use CultuurNet\UDB3\Ownership\Events\OwnershipRejected;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\RecordedOn;
use CultuurNet\UDB3\User\UserIdentityResolver;

final class OwnershipLDProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait {
        DelegateEventHandlingToSpecificMethodTrait::handle as handleMethodSpecificEvents;
    }

    private DocumentRepository $repository;
    private UserIdentityResolver $userIdentityResolver;

    public function __construct(
        DocumentRepository $repository,
        UserIdentityResolver $userIdentityResolver
    ) {
        $this->repository = $repository;
        $this->userIdentityResolver = $userIdentityResolver;
    }

    public function handle(DomainMessage $domainMessage): void
    {
        $event = $domainMessage->getPayload();

        $handleMethod = $this->getHandleMethodName($event);
        if (!$handleMethod) {
            return;
        }

        $jsonDocument = $this->{$handleMethod}($event, $domainMessage);

        if ($jsonDocument) {
            $jsonDocument = $this->updateModified($jsonDocument, $domainMessage);
            $this->repository->save($jsonDocument);
        }
    }

    public function applyOwnershipRequested(OwnershipRequested $ownershipRequested, DomainMessage $domainMessage): JsonDocument
    {
        $ownerDetails = $this->userIdentityResolver->getUserById($ownershipRequested->getOwnerId());
        $requesterDetails = $this->userIdentityResolver->getUserById($ownershipRequested->getRequesterId());

        $jsonDocument = new JsonDocument($ownershipRequested->getId());

        $body = $jsonDocument->getBody();

        $body->id = $ownershipRequested->getId();
        $body->itemId = $ownershipRequested->getItemId();
        $body->itemType = $ownershipRequested->getItemType();
        $body->ownerId = $ownershipRequested->getOwnerId();
        $body->ownerEmail = $ownerDetails !== null ? $ownerDetails->getEmailAddress() : null;
        $body->requesterId = $ownershipRequested->getRequesterId();
        $body->requesterEmail = $requesterDetails !== null ? $requesterDetails->getEmailAddress() : null;
        $body->state = OwnershipState::requested()->toString();

        $body->created = DateTimeFactory::fromFormat(
            DateTime::FORMAT_STRING,
            $domainMessage->getRecordedOn()->toString()
        )->format('c');

        return $jsonDocument->withBody($body);
    }

    public function applyOwnershipApproved(OwnershipApproved $ownershipApproved, DomainMessage $domainMessage): JsonDocument
    {
        $jsonDocument = $this->repository->fetch($ownershipApproved->getId());

        $body = $jsonDocument->getBody();
        $body->state = OwnershipState::approved()->toString();

        $userId = $domainMessage->getMetadata()->get('user_id');
        $approverDetails = $this->userIdentityResolver->getUserById($userId);
        $body->approvedById = $userId;
        $body->approvedByEmail = $approverDetails !== null ? $approverDetails->getEmailAddress() : null;

        $recordedDateTime = RecordedOn::fromDomainMessage($domainMessage);
        $body->approvedDate = $recordedDateTime->toString();

        return $jsonDocument->withBody($body);
    }

    public function applyOwnershipRejected(OwnershipRejected $ownershipRejected, DomainMessage $domainMessage): JsonDocument
    {
        $jsonDocument = $this->repository->fetch($ownershipRejected->getId());

        $body = $jsonDocument->getBody();
        $body->state = OwnershipState::rejected()->toString();

        $userId = $domainMessage->getMetadata()->get('user_id');
        $rejecterDetails = $this->userIdentityResolver->getUserById($userId);
        $body->rejectedById = $userId;
        $body->rejectedByEmail = $rejecterDetails !== null ? $rejecterDetails->getEmailAddress() : null;

        $recordedDateTime = RecordedOn::fromDomainMessage($domainMessage);
        $body->rejectedDate = $recordedDateTime->toString();

        return $jsonDocument->withBody($body);
    }

    public function applyOwnershipDeleted(OwnershipDeleted $ownershipDeleted, DomainMessage $domainMessage): JsonDocument
    {
        $jsonDocument = $this->repository->fetch($ownershipDeleted->getId());

        $body = $jsonDocument->getBody();
        $body->state = OwnershipState::deleted()->toString();

        $userId = $domainMessage->getMetadata()->get('user_id');
        $deleterDetails = $this->userIdentityResolver->getUserById($userId);
        $body->deletedById = $userId;
        $body->deletedByEmail = $deleterDetails !== null ? $deleterDetails->getEmailAddress() : null;

        $recordedDateTime = RecordedOn::fromDomainMessage($domainMessage);
        $body->deletedDate = $recordedDateTime->toString();

        return $jsonDocument->withBody($body);
    }

    private function updateModified(JsonDocument $jsonDocument, DomainMessage $domainMessage): JsonDocument
    {
        $body = $jsonDocument->getBody();

        $recordedDateTime = RecordedOn::fromDomainMessage($domainMessage);
        $body->modified = $recordedDateTime->toString();

        return $jsonDocument->withBody($body);
    }
}
