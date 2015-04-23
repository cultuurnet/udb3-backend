<?php
/**
 * @file
 */

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\EventSourcing\DBAL\EventStream;
use CultuurNet\UDB3\UDB2\EventImporterInterface;
use CultuurNet\UDB3\UDB2\EventNotFoundException;
use Knp\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateCdbXMLCommand extends Command
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('udb2:update-imported-events')
            ->setDescription(
                'Checks if all events imported from UDB2 have the latest changes and if not updates them'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $errOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;

        $stream = $this->getEventStream();
        $eventImporter = $this->getEventImporter();

        /** @var DomainEventStream $eventStream */
        foreach ($stream() as $eventStream) {
            /** @var DomainMessage $message */
            foreach ($eventStream->getIterator() as $message) {
                if ($message->getType() !== 'CultuurNet.UDB3.Event.EventImportedFromUDB2') {
                    continue;
                }

                $output->writeln(
                    $message->getRecordedOn()->toString() . ' ' .
                    $message->getType() .
                    ' (' . $message->getId() . ')'
                );

                try {
                    $eventImporter->updateEventFromUDB2($message->getId());
                }
                catch (EventNotFoundException $e) {
                    $errOutput->writeln(
                        "<error>{$e->getMessage()} Probably the last occurrence of the event was in the past, and events that occurred in the past are by default not returned by the API. If this is the case, you can ignore this message.</error>"
                    );
                }
            }
        }
    }

    /**
     * @param string $store
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

    /**
     * @return EventImporterInterface
     */
    private function getEventImporter()
    {
        $app = $this->getSilexApplication();

        return $app['udb2_event_importer'];
    }

}
