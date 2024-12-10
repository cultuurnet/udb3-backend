<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Label\Events\LabelDetailsProjectedToJSONLD;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;

final class BroadcastingWriteRepositoryDecorator implements WriteRepositoryInterface
{
    private EventBus $eventBus;

    private WriteRepositoryInterface $writeRepository;

    public function __construct(WriteRepositoryInterface $writeRepository, EventBus $eventBus)
    {
        $this->writeRepository = $writeRepository;
        $this->eventBus = $eventBus;
    }

    public function save(
        UUID $uuid,
        string $name,
        Visibility $visibility,
        Privacy $privacy
    ): void {
        $this->writeRepository->save(
            $uuid,
            $name,
            $visibility,
            $privacy
        );
    }

    public function updatePrivate(UUID $uuid): void
    {
        $this->writeRepository->updatePrivate($uuid);
        $this->broadcastDocumentUpdated($uuid);
    }

    public function updatePublic(UUID $uuid): void
    {
        $this->writeRepository->updatePublic($uuid);
        $this->broadcastDocumentUpdated($uuid);
    }

    public function updateVisible(UUID $uuid): void
    {
        $this->writeRepository->updateVisible($uuid);
        $this->broadcastDocumentUpdated($uuid);
    }

    public function updateInvisible(UUID $uuid): void
    {
        $this->writeRepository->updateInvisible($uuid);
        $this->broadcastDocumentUpdated($uuid);
    }

    public function updateIncluded(UUID $uuid): void
    {
        $this->writeRepository->updateIncluded($uuid);
    }

    public function updateExcluded(UUID $uuid): void
    {
        $this->writeRepository->updateExcluded($uuid);
    }

    private function broadcastDocumentUpdated(UUID $uuid): void
    {
        $event = new LabelDetailsProjectedToJSONLD($uuid);

        $generator = new Version4Generator();
        $events = [
            DomainMessage::recordNow(
                $generator->generate(),
                1,
                new Metadata(),
                $event
            ),
        ];

        $this->eventBus->publish(
            new DomainEventStream($events)
        );
    }
}
