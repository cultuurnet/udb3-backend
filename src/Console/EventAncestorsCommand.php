<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\Domain\DomainMessage;
use Broadway\EventStore\EventStoreInterface;
use CultuurNet\UDB3\EventSourcing\AggregateCopiedEventInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EventAncestorsCommand extends AbstractCommand
{
    public function configure()
    {
        $this
            ->setName('event:ancestors')
            ->setDescription('Get all ancestors of an event.')
            ->addArgument(
                'cdbid',
                InputArgument::REQUIRED,
                'The cdbid of the event to get the ancestors from.',
                null
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $cdbid = $input->getArgument('cdbid');
        $eventStore = $this->getEventStore();

        $ancestors = [];
        $eventStream = $eventStore->load($cdbid);
        foreach ($eventStream->getIterator() as $event) {
            /** @var DomainMessage $event */
            $domainEvent = $event->getPayload();
            if ($domainEvent instanceof AggregateCopiedEventInterface) {
                $ancestors[] = $domainEvent->getParentAggregateId();
            }
        }

        foreach ($ancestors as $ancestor) {
            $output->writeln($ancestor);
        }
        $output->writeln($cdbid);
    }

    /**
     * @return EventStoreInterface
     */
    private function getEventStore()
    {
        $app = $this->getSilexApplication();
        return $app['event_store'];
    }
}
