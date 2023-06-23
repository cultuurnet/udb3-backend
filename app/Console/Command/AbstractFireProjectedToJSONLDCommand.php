<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\EventBus\Middleware\ReplayFlaggingMiddleware;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractFireProjectedToJSONLDCommand extends Command
{
    private EventBus $eventBus;

    private DocumentEventFactory $organizerEventFactory;

    private DocumentEventFactory $placeEventFactory;

    public function __construct(EventBus $eventBus, DocumentEventFactory $organizerEventFactory, DocumentEventFactory $placeEventFactory)
    {
        parent::__construct();
        $this->eventBus = $eventBus;
        $this->organizerEventFactory = $organizerEventFactory;
        $this->placeEventFactory = $placeEventFactory;
    }

    protected function getEventFactory(string $type): DocumentEventFactory
    {
        if ($type === 'organizer') {
            return $this->organizerEventFactory;
        }

        return $this->placeEventFactory;
    }

    protected function inReplayMode(
        callable $callback,
        InputInterface $input,
        OutputInterface $output
    ): void {
        ReplayFlaggingMiddleware::startReplayMode();
        $callback($this->eventBus, $input, $output);
        ReplayFlaggingMiddleware::stopReplayMode();
    }

    protected function fireEvent(
        string $id,
        DocumentEventFactory $eventFactory,
        OutputInterface $output,
        DomainMessageBuilder $domainMessageBuilder,
        EventBus $eventBus
    ): void {
        $event = $eventFactory->createEvent($id);
        $output->writeln($event->getIri());

        $domainMessage = $domainMessageBuilder->create($event);

        try {
            $eventBus->publish(
                new DomainEventStream([$domainMessage])
            );
        } catch (EntityNotFoundException $e) {
            $output->writeln($e->getMessage());
        }
    }
}
