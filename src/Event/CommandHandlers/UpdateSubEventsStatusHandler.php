<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\CommandHandlers;

use Broadway\CommandHandling\CommandHandlerInterface;
use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Event\Commands\UpdateSubEventsStatus;
use CultuurNet\UDB3\Event\Event;

final class UpdateSubEventsStatusHandler implements CommandHandlerInterface
{
    /**
     * @var RepositoryInterface
     */
    protected $offerRepository;

    public function __construct(RepositoryInterface $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    public function handle($command)
    {
        if (!($command instanceof UpdateSubEventsStatus)) {
            return;
        }

        /** @var Event $event */
        $event = $this->offerRepository->load($command->getItemId());
        $event->updateSubEventsStatus($command->getEventStatuses());
        $this->offerRepository->save($event);
    }
}
