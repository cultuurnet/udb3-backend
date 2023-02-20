<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;

final class BroadcastingContributorRepository implements ContributorRepository
{
    private ContributorRepository $repository;

    private EventBus $eventBus;

    private ContributorsUpdatedFactory $contributorOverwrittenFactory;

    public function __construct(
        ContributorRepository $repository,
        EventBus $eventBus,
        ContributorsUpdatedFactory $contributorOverwrittenFactory
    ) {
        $this->repository = $repository;
        $this->eventBus = $eventBus;
        $this->contributorOverwrittenFactory = $contributorOverwrittenFactory;
    }

    public function getContributors(UUID $id): EmailAddresses
    {
        return $this->repository->getContributors($id);
    }

    public function isContributor(UUID $id, EmailAddress $emailAddress): bool
    {
        return $this->repository->isContributor($id, $emailAddress);
    }

    public function updateContributors(UUID $id, EmailAddresses $emailAddresses, ItemType $itemType): void
    {
        $this->repository->updateContributors($id, $emailAddresses, $itemType);

        $contributorsUpdated = $this->contributorOverwrittenFactory->createForItemType($id->toString(), $itemType);

        $this->eventBus->publish(new DomainEventStream([(new DomainMessageBuilder())->create($contributorsUpdated)]));
    }
}
