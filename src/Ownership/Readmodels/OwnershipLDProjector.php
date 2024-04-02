<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Ownership\Readmodels;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\EventHandling\DelegateEventHandlingToSpecificMethodTrait;
use CultuurNet\UDB3\Ownership\Events\OwnershipApproved;
use CultuurNet\UDB3\Ownership\Events\OwnershipRejected;
use CultuurNet\UDB3\Ownership\Events\OwnershipRequested;
use CultuurNet\UDB3\Ownership\OwnershipState;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\RecordedOn;

final class OwnershipLDProjector implements EventListener
{
    use DelegateEventHandlingToSpecificMethodTrait {
        DelegateEventHandlingToSpecificMethodTrait::handle as handleMethodSpecificEvents;
    }

    private DocumentRepository $repository;

    public function __construct(DocumentRepository $repository)
    {
        $this->repository = $repository;
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
        $jsonDocument = new JsonDocument($ownershipRequested->getId());

        $body = $jsonDocument->getBody();

        $body->id = $ownershipRequested->getId();
        $body->itemId = $ownershipRequested->getItemId();
        $body->itemType = $ownershipRequested->getItemType();
        $body->ownerId = $ownershipRequested->getOwnerId();
        $body->requesterId = $ownershipRequested->getRequesterId();
        $body->state = OwnershipState::requested()->toString();

        $body->created = \DateTime::createFromFormat(
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

        return $jsonDocument->withBody($body);
    }

    public function applyOwnershipRejected(OwnershipRejected $ownershipRejected, DomainMessage $domainMessage): JsonDocument
    {
        $jsonDocument = $this->repository->fetch($ownershipRejected->getId());

        $body = $jsonDocument->getBody();
        $body->state = OwnershipState::rejected()->toString();

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
