<?php

namespace CultuurNet\UDB3\Event;

use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\CommandHandling\Udb3CommandHandler;
use CultuurNet\UDB3\Event\Commands\Conclude;

class ConcludeCommandHandler extends Udb3CommandHandler
{
    /**
     * @var RepositoryInterface
     */
    protected $offerRepository;

    /**
     * ConcludeCommandHandler constructor.
     *
     * @param RepositoryInterface $offerRepository
     */
    public function __construct(RepositoryInterface $offerRepository)
    {
        $this->offerRepository = $offerRepository;
    }

    /**
     * @param Conclude $conclude
     */
    public function handleConclude(Conclude $conclude)
    {
        /** @var Event $event */
        $event = $this->offerRepository->load($conclude->getItemId());

        $event->conclude();

        $this->offerRepository->save($event);
    }
}
