<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FireProjectedToJSONLDForRelationsCommand extends AbstractFireProjectedToJSONLDCommand
{
    private Connection $connection;

    public function __construct(EventBus $eventBus, Connection $connection, DocumentEventFactory $organizerEventFactory, DocumentEventFactory $placeEventFactory)
    {
        parent::__construct($eventBus, $organizerEventFactory, $placeEventFactory);
        $this->connection = $connection;
    }

    protected function configure(): void
    {
        $this
            ->setName('fire-projected-to-jsonld-for-relations')
            ->setDescription('Fires JSONLD projected events for organizers and places having relations');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $connection = $this->connection;

        $this->inReplayMode(
            function (
                EventBus $eventBus,
                InputInterface $input,
                OutputInterface $output
            ) use ($connection): void {
                $domainMessageBuilder = new DomainMessageBuilder();

                $queryBuilder = $this->connection->createQueryBuilder();

                $eventOrganizers = $queryBuilder->select('DISTINCT organizer')
                    ->from('event_relations')
                    ->where('organizer IS NOT NULL')
                    ->execute()
                    ->fetchFirstColumn();

                $queryBuilder = $connection->createQueryBuilder();
                $placeOrganizers = $queryBuilder->select('DISTINCT organizer')
                    ->from('place_relations')
                    ->where('organizer IS NOT NULL')
                    ->execute()
                    ->fetchFirstColumn();

                $allOrganizers = array_merge($eventOrganizers, $placeOrganizers);
                $allOrganizers = array_unique($allOrganizers);

                $output->writeln('Organizers of events and places:');

                $this->fireEvents(
                    $allOrganizers,
                    $this->getEventFactory('organizer'),
                    $output,
                    $domainMessageBuilder,
                    $eventBus
                );

                $output->writeln('');
                $output->writeln('');
                $output->writeln('Places at which events are located:');

                $queryBuilder = $connection->createQueryBuilder();
                $eventLocations = $queryBuilder->select('DISTINCT place')
                    ->from('event_relations')
                    ->where('place IS NOT NULL')
                    ->execute()
                    ->fetchFirstColumn();

                $this->fireEvents(
                    $eventLocations,
                    $this->getEventFactory('place'),
                    $output,
                    $domainMessageBuilder,
                    $eventBus
                );
            },
            $input,
            $output
        );

        return 0;
    }

    protected function fireEvents(
        array $ids,
        DocumentEventFactory $eventFactory,
        OutputInterface $output,
        DomainMessageBuilder $domainMessageBuilder,
        EventBus $eventBus
    ): void {
        foreach ($ids as $key => $id) {
            $output->writeln($key . ': ' . $id);

            $this->fireEvent(
                $id,
                $eventFactory,
                $output,
                $domainMessageBuilder,
                $eventBus
            );
        }
    }
}
