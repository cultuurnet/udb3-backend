<?php

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\EventHandling\EventBusInterface;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;
use Doctrine\DBAL\Connection;
use PDO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FireProjectedToJSONLDForRelationsCommand extends AbstractFireProjectedToJSONLDCommand
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(EventBusInterface $eventBus, Connection $connection, DocumentEventFactory $organizerEventFactory, DocumentEventFactory $placeEventFactory)
    {
        parent::__construct($eventBus, $organizerEventFactory, $placeEventFactory);
        $this->connection = $connection;
    }

    protected function configure()
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
                EventBusInterface $eventBus,
                InputInterface $input,
                OutputInterface $output
            ) use ($connection) {
                $domainMessageBuilder = new DomainMessageBuilder();

                $queryBuilder = $this->connection->createQueryBuilder();

                $eventOrganizers = $queryBuilder->select('DISTINCT organizer')
                    ->from('event_relations')
                    ->where('organizer IS NOT NULL')
                    ->execute()
                    ->fetchAll(PDO::FETCH_COLUMN);

                $queryBuilder = $connection->createQueryBuilder();
                $placeOrganizers = $queryBuilder->select('DISTINCT organizer')
                    ->from('place_relations')
                    ->where('organizer IS NOT NULL')
                    ->execute()
                    ->fetchAll(PDO::FETCH_COLUMN);

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
                    ->fetchAll(PDO::FETCH_COLUMN);

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
        EventBusInterface $eventBus
    ) {
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
