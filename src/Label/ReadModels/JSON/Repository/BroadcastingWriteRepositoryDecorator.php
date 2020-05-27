<?php

namespace CultuurNet\UDB3\Label\ReadModels\JSON\Repository;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBusInterface;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Label\Events\LabelDetailsProjectedToJSONLD;
use CultuurNet\UDB3\Label\ValueObjects\Privacy;
use CultuurNet\UDB3\Label\ValueObjects\Visibility;
use ValueObjects\Identity\UUID;
use ValueObjects\StringLiteral\StringLiteral;

class BroadcastingWriteRepositoryDecorator implements WriteRepositoryInterface
{
    /**
     * @var EventBusInterface
     */
    private $eventBus;

    /**
     * @var WriteRepositoryInterface
     */
    private $writeRepository;

    /**
     * @param \CultuurNet\UDB3\Label\ReadModels\JSON\Repository\WriteRepositoryInterface $writeRepository
     * @param \Broadway\EventHandling\EventBusInterface $eventBus
     */
    public function __construct(WriteRepositoryInterface $writeRepository, EventBusInterface $eventBus)
    {
        $this->writeRepository = $writeRepository;
        $this->eventBus = $eventBus;
    }

    /**
     * @inheritDoc
     */
    public function save(
        UUID $uuid,
        StringLiteral $name,
        Visibility $visibility,
        Privacy $privacy,
        UUID $parentUuid = null
    ) {
        $this->writeRepository->save(
            $uuid,
            $name,
            $visibility,
            $privacy,
            $parentUuid
        );
    }

    /**
     * @inheritDoc
     */
    public function updateCountIncrement(UUID $uuid)
    {
        $this->writeRepository->updateCountIncrement($uuid);
    }

    /**
     * @inheritDoc
     */
    public function updateCountDecrement(UUID $uuid)
    {
        $this->writeRepository->updateCountDecrement($uuid);
    }

    /**
     * @inheritDoc
     */
    public function updatePrivate(UUID $uuid)
    {
        $this->writeRepository->updatePrivate($uuid);
        $this->broadcastDocumentUpdated($uuid);
    }

    /**
     * @inheritDoc
     */
    public function updatePublic(UUID $uuid)
    {
        $this->writeRepository->updatePublic($uuid);
        $this->broadcastDocumentUpdated($uuid);
    }

    /**
     * @inheritDoc
     */
    public function updateVisible(UUID $uuid)
    {
        $this->writeRepository->updateVisible($uuid);
        $this->broadcastDocumentUpdated($uuid);
    }

    /**
     * @inheritDoc
     */
    public function updateInvisible(UUID $uuid)
    {
        $this->writeRepository->updateInvisible($uuid);
        $this->broadcastDocumentUpdated($uuid);
    }

    /**
     * @param \ValueObjects\Identity\UUID $uuid
     */
    protected function broadcastDocumentUpdated(UUID $uuid)
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
