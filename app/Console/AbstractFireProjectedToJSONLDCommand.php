<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\Broadway\EventHandling\ReplayModeEventBusInterface;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;
use CultuurNet\UDB3\Silex\Organizer\OrganizerJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Place\PlaceJSONLDServiceProvider;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class AbstractFireProjectedToJSONLDCommand extends Command
{
    /**
     * @var EventBusInterface
     */
    private $eventBus;

    public function __construct(EventBusInterface $eventBus)
    {
        parent::__construct();
        $this->eventBus = $eventBus;
    }

    protected function getEventFactory(string $type): DocumentEventFactory
    {
        $app = $this->getSilexApplication();

        switch ($type) {
            case 'organizer':
                return $app[OrganizerJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY];
            case 'place':
            default:
                return $app[PlaceJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY];
        }
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

    /**
     * @param $id
     * @param \CultuurNet\UDB3\ReadModel\DocumentEventFactory $eventFactory
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @param \CultuurNet\UDB3\EventSourcing\DomainMessageBuilder $domainMessageBuilder
     * @param \Broadway\EventHandling\EventBusInterface $eventBus
     */
    protected function fireEvent(
        $id,
        DocumentEventFactory $eventFactory,
        OutputInterface $output,
        DomainMessageBuilder $domainMessageBuilder,
        EventBusInterface $eventBus
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
