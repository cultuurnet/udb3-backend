<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Event\Commands\UpdateFaqItem;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventRepository;

final class UpdateFaqItemHandler implements CommandHandler
{
    public function __construct(private readonly EventRepository $eventRepository)
    {
    }

    public function handle($command): void
    {
        if (!$command instanceof UpdateFaqItem) {
            return;
        }

        /** @var Event $event */
        $event = $this->eventRepository->load($command->getItemId());

        $event->updateFaqItem($command->faqItem);

        $this->eventRepository->save($event);
    }
}
