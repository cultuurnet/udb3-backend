<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandlerInterface;
use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Event\Commands\Status\UpdateStatus;
use CultuurNet\UDB3\Event\Event;

class UpdateStatusHandler implements CommandHandlerInterface
{
    /**
     * @var RepositoryInterface
     */
    private $eventRepository;

    public function __construct(RepositoryInterface $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof UpdateStatus)) {
            return;
        }

        /** @var Event $event */
        $event = $this->eventRepository->load($command->getItemId());
        $event->updateStatus($command->getStatus());
        $this->eventRepository->save($event);
    }
}
