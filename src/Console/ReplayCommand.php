<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\EventSourcing\DBAL\EventStream;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReplayCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('replay')
            ->setDescription('Replay the event stream to the event bus with only read models attached.');
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stream = $this->getEventStream();

        $eventBus = $this->getEventBus();

        /** @var DomainEventStream $eventStream */
        foreach ($stream() as $eventStream) {
            /** @var DomainMessage $message */
            foreach ($eventStream->getIterator() as $message) {
                $output->writeln(
                    $message->getRecordedOn()->toString() . ' ' .
                    $message->getType() .
                    ' (' . $message->getId() . ')'
                );
            }

            $eventBus->publish($eventStream);
        }
    }

    /**
     * @return EventBusInterface
     */
    private function getEventBus()
    {
        $app = $this->getSilexApplication();

        // @todo Limit the event bus to read projections.
        return $app['event_bus'];
    }

    /**
     * @return EventStream
     */
    private function getEventStream()
    {
        $app = $this->getSilexApplication();

        return new EventStream(
            $app['dbal_connection'],
            $app['eventstore_payload_serializer'],
            new \Broadway\Serializer\SimpleInterfaceSerializer(),
            'events'
        );
    }
}
