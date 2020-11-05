<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event;

use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Event\Commands\Status\UpdateSubEventStatus;

final class UpdateSubEventStatusHandler extends Udb3CommandHandler
{
    /**
     * @var RepositoryInterface
     */
    protected $eventRepository;

    public function __construct(RepositoryInterface $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function handleUpdateSubEventStatus(UpdateSubEventStatus $updateSubEventStatus): void
    {
        /** @var Event $event */
        $event = $this->eventRepository->load($updateSubEventStatus->getItemId());

        $event->updateSubEventStatus(
            $updateSubEventStatus->getStatus(),
            $updateSubEventStatus->getTimestamp(),
            $updateSubEventStatus->getReason()
        );

        $this->eventRepository->save($event);
    }
}
