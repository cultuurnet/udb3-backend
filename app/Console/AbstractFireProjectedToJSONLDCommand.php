<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Broadway\EventHandling\ReplayModeEventBusInterface;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractFireProjectedToJSONLDCommand extends Command
{
    /**
     * @var EventBus
     */
    private $eventBus;

    /**
     * @var DocumentEventFactory
     */
    private $organizerEventFactory;

    /**
     * @var DocumentEventFactory
     */
    private $placeEventFactory;

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
    ) {
        if ($this->eventBus instanceof ReplayModeEventBusInterface) {
            $this->eventBus->startReplayMode();
        } else {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion(
                'Warning! The current event bus does not flag replay messages. '
                . 'This might trigger unintended changes. Continue anyway? [y/N] ',
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                return;
            }
        }

        $callback($this->eventBus, $input, $output);

        if ($this->eventBus instanceof ReplayModeEventBusInterface) {
            $this->eventBus->stopReplayMode();
        }
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
