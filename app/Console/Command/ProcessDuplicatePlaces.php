<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use Broadway\Domain\DomainEventStream;
use Broadway\EventHandling\EventBus;
use CultuurNet\UDB3\Event\Commands\UpdateLocation;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventRelationsRepository;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Place\Canonical\CanonicalService;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRemovedFromClusterRepository;
use CultuurNet\UDB3\Place\Canonical\DuplicatePlaceRepository;
use CultuurNet\UDB3\Place\Canonical\Exception\MuseumPassNotUniqueInCluster;
use CultuurNet\UDB3\ReadModel\DocumentEventFactory;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

final class ProcessDuplicatePlaces extends AbstractCommand
{
    private const ONLY_RUN_CLUSTER_ID = 'only-run-cluster-id';
    private const FORCE = 'force';
    private DuplicatePlaceRepository $duplicatePlaceRepository;

    private CanonicalService $canonicalService;

    private EventRelationsRepository $eventRelationsRepository;

    private EventBus $eventBus;

    private DocumentEventFactory $placeEventFactory;

    private DuplicatePlaceRemovedFromClusterRepository $duplicatePlaceRemovedFromClusterRepository;

    public function __construct(
        CommandBus $commandBus,
        DuplicatePlaceRepository $duplicatePlaceRepository,
        DuplicatePlaceRemovedFromClusterRepository $duplicatePlaceRemovedFromClusterRepository,
        CanonicalService $canonicalService,
        EventBus $eventBus,
        DocumentEventFactory $placeEventFactory,
        EventRelationsRepository $eventRelationsRepository
    ) {
        $this->duplicatePlaceRepository = $duplicatePlaceRepository;
        $this->duplicatePlaceRemovedFromClusterRepository = $duplicatePlaceRemovedFromClusterRepository;
        $this->canonicalService = $canonicalService;
        $this->eventBus = $eventBus;
        $this->placeEventFactory = $placeEventFactory;
        $this->eventRelationsRepository = $eventRelationsRepository;

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
        $this->addOption(
            'only-set-canonical',
            'c',
            InputOption::VALUE_NONE,
            'Execute the script but only set the canonical of the clusters, do not reindex or update event locations.'
        );
        $this->addArgument(
            'start-cluster-id',
            InputArgument::OPTIONAL,
            'The id of the cluster to start processing from (useful for resuming a previous run).'
        );
        $this->addOption(
            self::ONLY_RUN_CLUSTER_ID,
            'id',
            InputOption::VALUE_REQUIRED,
            'The id of the cluster you want to proces.'
        );
        $this->addOption(self::FORCE, 'f', InputOption::VALUE_NONE, 'Skip confirmation.');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $dryRun = (bool)$input->getOption('dry-run');
        $startingClusterId = (int)$input->getArgument('start-cluster-id');
        $onlySetCanonical = (bool)$input->getOption('only-set-canonical');
        $onlyRunClusterId = $input->getOption(self::ONLY_RUN_CLUSTER_ID);

        if ($onlyRunClusterId) {
            $clusterIds = [(int)$onlyRunClusterId];
        } else {
            $clusterIds = $this->duplicatePlaceRepository->getClusterIdsWithoutCanonical();
        }

        if (count($clusterIds) === 0) {
            $output->writeln('No clusters found to process');
            return 0;
        }

        if (!$this->askConfirmation($input, $output, count($clusterIds))) {
            return 0;
        }

        foreach ($clusterIds as $clusterId) {
            if ($clusterId < $startingClusterId) {
                continue;
            }

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

            if ($onlySetCanonical) {
                continue;
            }

            // 2. Trigger a SAPI3 reindex on the places in duplicate_places
            $this->reindexPlaces($this->duplicatePlaceRepository->getPlacesInCluster($clusterId), $output, $dryRun);

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

        if (!$onlySetCanonical) {
            // 4. Trigger a SAPI3 reindex on the places removed from duplicate_places

            $this->reindexPlaces($this->duplicatePlaceRemovedFromClusterRepository->getDuplicatePlacesRemovedFromCluster(), $output, $dryRun);
            $this->duplicatePlaceRemovedFromClusterRepository->truncateTable();
        }

        return self::SUCCESS;
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, int $count): bool
    {
        if ($input->getOption(self::FORCE) === true) {
            $output->writeln("This action will process {$count} clusters");
            return true;
        }

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

    private function reindexPlaces(array $placesToReIndex, OutputInterface $output, bool $dryRun): void
    {
        foreach ($placesToReIndex as $placeToReIndex) {
            $placeProjected = $this->placeEventFactory->createEvent($placeToReIndex);
            $output->writeln('Dispatching PlaceProjectedToJSONLD for place with id ' . $placeToReIndex);
            if (!$dryRun) {
                $this->eventBus->publish(
                    new DomainEventStream([(new DomainMessageBuilder())->create($placeProjected)])
                );
            }
        }
    }
}
