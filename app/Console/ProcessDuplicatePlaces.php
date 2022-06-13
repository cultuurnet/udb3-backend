<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Broadway\AMQP\AMQPPublisher;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\ReadModel\Relations\RepositoryInterface;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Place\Canonical\CanonicalService;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;
use CultuurNet\UDB3\Place\Canonical\Exception\MuseumPassNotUniqueInCluster;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;
use Doctrine\DBAL\Connection;
use PDO;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class ProcessDuplicatePlaces extends AbstractCommand
{
    private DuplicatePlaceRepository $duplicatePlaceRepository;

    private CanonicalService $canonicalService;

    private RepositoryInterface $eventRelationsRepository;

    private AMQPPublisher $amqpPublisher;

    private DocumentEventFactory $placeEventFactory;

    private Connection $connection;

    public function __construct(
        CommandBus $commandBus,
        DuplicatePlaceRepository $duplicatePlaceRepository,
        CanonicalService $canonicalService,
        AMQPPublisher $amqpPublisher,
        DocumentEventFactory $placeEventFactory,
        RepositoryInterface $eventRelationsRepository,
        Connection $connection
    ) {
        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
        $this->canonicalService = $canonicalService;
        $this->amqpPublisher = $amqpPublisher;
        $this->placeEventFactory = $placeEventFactory;
        $this->eventRelationsRepository = $eventRelationsRepository;
        $this->connection = $connection;

        parent::__construct($commandBus);
    }

    public function configure(): void
    {
        $this->setName('place:process-duplicates');
        $this->setDescription('Process duplicate places (determine canonical, update event locations and reindex)');
        $this->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Execute a dry-run of the process-duplicates script.'
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool)$input->getOption('dry-run');

        $clusterIds = $this->duplicatePlaceRepository->getClusterIds();

        if (count($clusterIds) === 0) {
            $output->writeln('No clusters found to process');
            return 0;
        }

        if (!$this->askConfirmation($input, $output, count($clusterIds))) {
            return 0;
        }

        foreach ($clusterIds as $clusterId) {
            // 1. Set the canonical of a cluster
            try {
                $canonicalId = $this->canonicalService->getCanonical($clusterId);
            } catch (MuseumPassNotUniqueInCluster $museumPassNotUniqueInClusterException) {
                $output->writeln($museumPassNotUniqueInClusterException->getMessage());
                continue;
            }

            $output->writeln('Setting ' . $canonicalId . ' as canonical of cluster ' . $clusterId);
            if (!$dryRun) {
                $this->duplicatePlaceRepository->setCanonicalOnCluster($clusterId, $canonicalId);
            }

            // 2. Trigger a SAPI3 reindex on the places in duplicate_places and places removed from duplicate_places
            $placesToReIndex = array_merge(
                $this->duplicatePlaceRepository->getPlacesInCluster($clusterId),
                $this->getDuplicatePlacesRemovedFromCluster()
            );
            foreach ($placesToReIndex as $placeToReIndex) {
                $placeProjected = $this->placeEventFactory->createEvent($placeToReIndex);
                $output->writeln('Dispatching PlaceProjectedToJSONLD for place with id ' . $placeToReIndex);
                if (!$dryRun) {
                    $this->amqpPublisher->handle((new DomainMessageBuilder())->create($placeProjected));
                }
            }

            // 3. Trigger an UpdateLocation for places inside duplicate_places
            $duplicatePlaces = $this->duplicatePlaceRepository->getDuplicatesOfPlace($canonicalId);
            if ($duplicatePlaces === null) {
                continue;
            }

            foreach ($duplicatePlaces as $duplicatePlace) {
                $commands = [];

                $eventsLocatedAtDuplicatePlace = $this->eventRelationsRepository->getEventsLocatedAtPlace($duplicatePlace);

                foreach ($eventsLocatedAtDuplicatePlace as $eventLocatedAtDuplicatePlace) {
                    $commands[] = new UpdateLocation($eventLocatedAtDuplicatePlace, new LocationId($canonicalId));
                }

                foreach ($commands as $command) {
                    $output->writeln('Dispatching UpdateLocation for event with id ' . $command->getItemId());
                    if (!$dryRun) {
                        $this->commandBus->dispatch($command);
                    }
                }
            }
        }

        return 0;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, int $count): bool
    {
        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    "This action will process {$count} clusters, continue? [y/N] ",
                    false
                )
            );
    }

    private function getDuplicatePlacesRemovedFromCluster(): array
    {
        return $this->connection->createQueryBuilder()
            ->select('place_uuid')
            ->from('duplicate_places_removed_from_cluster')
            ->execute()
            ->fetchAll(PDO::FETCH_COLUMN);
    }
}
