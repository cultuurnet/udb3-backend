<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\ProjectedToJSONLDFactory;

final class BroadcastingContributorRepository implements ContributorRepository
{
    private ContributorRepository $repository;

    private EventBus $eventBus;

    private ProjectedToJSONLDFactory $projectedToJSONLDFactory;

    public function __construct(
        ContributorRepository $repository,
        EventBus $eventBus,
        ProjectedToJSONLDFactory $projectedToJSONLDFactory
    ) {
        $this->repository = $repository;
        $this->eventBus = $eventBus;
        $this->projectedToJSONLDFactory = $projectedToJSONLDFactory;
    }

    public function getContributors(Uuid $id): EmailAddresses
    {
        return $this->repository->getContributors($id);
    }

    public function isContributor(Uuid $id, EmailAddress $emailAddress): bool
    {
        return $this->repository->isContributor($id, $emailAddress);
    }

    public function updateContributors(Uuid $id, EmailAddresses $emailAddresses, ItemType $itemType): void
    {
        $this->repository->updateContributors($id, $emailAddresses, $itemType);

        $projectedToJSONLD = $this->projectedToJSONLDFactory->createForItemType($id->toString(), $itemType);

        $this->eventBus->publish(new DomainEventStream([(new DomainMessageBuilder())->create($projectedToJSONLD)]));
    }
}
