<?php

namespace CultuurNet\UDB3\Silex\Console;

use function array_merge;
use function array_unique;
use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBusInterface;
use CultuurNet\Broadway\EventHandling\ReplayModeEventBusInterface;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;
use CultuurNet\UDB3\Silex\Organizer\OrganizerJSONLDServiceProvider;
use CultuurNet\UDB3\Silex\Place\PlaceJSONLDServiceProvider;
use Knp\Command\Command;
use PDO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class FireProjectedToJSONLDForRelationsCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('fire-projected-to-jsonld-for-relations')
            ->setDescription('Fires JSONLD projected events for organizers and places having relations');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $app = $this->getSilexApplication();

        $domainMessageBuilder = new DomainMessageBuilder();

        /** @var \Doctrine\DBAL\Connection $connection */
        $connection = $app['dbal_connection'];

        /** @var \Broadway\EventHandling\EventBusInterface $eventBus */
        $eventBus = $app['event_bus'];

        if ($eventBus instanceof ReplayModeEventBusInterface) {
            $eventBus->startReplayMode();
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

        $queryBuilder = $connection->createQueryBuilder();

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
            $app[OrganizerJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY],
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
            $app[PlaceJSONLDServiceProvider::JSONLD_PROJECTED_EVENT_FACTORY],
            $output,
            $domainMessageBuilder,
            $eventBus
        );

        if ($eventBus instanceof ReplayModeEventBusInterface) {
            $eventBus->stopReplayMode();
        }
    }

    private function fireEvents(
        array $ids,
        DocumentEventFactory $eventFactory,
        OutputInterface $output,
        DomainMessageBuilder $domainMessageBuilder,
        EventBusInterface $eventBus
    ) {
        foreach ($ids as $key => $id) {
            $output->writeln($key . ': ' . $id);

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
}
