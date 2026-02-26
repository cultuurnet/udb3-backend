<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandler;
use CultuurNet\UDB3\Event\Commands\DeleteFaqItem;
use CultuurNet\UDB3\Event\Event;
use CultuurNet\UDB3\Event\EventRepository;

final class DeleteFaqItemHandler implements CommandHandler
{
    public function __construct(private readonly EventRepository $eventRepository)
    {
    }

    public function handle($command): void
    {
        if (!$command instanceof DeleteFaqItem) {
            return;
        }

        /** @var Event $event */
        $event = $this->eventRepository->load($command->getItemId());

        $event->deleteFaqItem($command->faqItemId);

        $this->eventRepository->save($event);
    }
}
