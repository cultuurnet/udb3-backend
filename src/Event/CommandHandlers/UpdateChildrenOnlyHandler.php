<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Event\Commands\UpdateChildrenOnly;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventRepository;

final class UpdateChildrenOnlyHandler implements CommandHandler
{
    public function __construct(private readonly EventRepository $eventRepository)
    {
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateChildrenOnly) {
            return;
        }

        /** @var Event $event */
        $event = $this->eventRepository->load($command->getItemId());

        $event->updateChildrenOnly($command->childrenOnly);

        $this->eventRepository->save($event);
    }
}
