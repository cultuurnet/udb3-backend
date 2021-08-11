<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use Broadway\Repository\Repository;
use CultuurNet\UDB3\Event\Commands\UpdateSubEvents;
use CultuurNet\UDB3\Event\Event;

final class UpdateSubEventsHandler implements CommandHandler
{
    private Repository $eventRepository;

    public function __construct(Repository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    public function handle($command): void
    {
        if (!($command instanceof UpdateSubEvents)) {
            return;
        }

        /** @var Event $event */
        $event = $this->eventRepository->load($command->getItemId());
        $event->updateSubEvents(...$command->getUpdates());
        $this->eventRepository->save($event);
    }
}
