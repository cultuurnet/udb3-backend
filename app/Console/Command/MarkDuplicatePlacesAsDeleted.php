<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Broadway\CommandHandling\CommandBus;
use CultuurNet\UDB3\Offer\Commands\DeleteOffer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * @url https://jira.publiq.be/browse/III-6109
 */
class MarkDuplicatePlacesAsDeleted extends AbstractCommand
{
    private const FILE = 'file';
    private const FORCE = 'force';
    private const DRY_RUN = 'dry-run';
    private const MIN_SIZE_CLUSTER = 50; //How many places should the cluster have before it is marked for deletion

    private Connection $connection;
    private LoggerInterface $logger;

    public function __construct(
        CommandBus $commandBus,
        Connection $connection,
        LoggerInterface $logger
    ) {
        parent::__construct($commandBus);
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function configure(): void
    {
        $this
            ->setName('place:delete-duplicate-places')
            ->setDescription('Check if the clusters are correct + if so, for the clusters that contain 50 places or more: set the workflowStatus to DELETED.')
            ->addArgument(
                self::FILE,
                null,
                'Skip confirmation.'
            )
            ->addOption(
                self::FORCE,
                null,
                InputOption::VALUE_NONE,
                'Skip confirmation.'
            )
            ->addOption(
                self::DRY_RUN,
                null,
                InputOption::VALUE_NONE,
                'Just show the ids of places you would delete, do not change them.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $serializer = new Serializer([new ArrayDenormalizer()], [new CsvEncoder()]);

        $placesFromCsv = $serializer->decode(file_get_contents($input->getArgument(self::FILE)), 'csv');


        if (!$this->askConfirmation($input, $output, count($placesFromCsv))) {
            return 0;
        }

        //@todo Wait, is this from the DB or the Import file.
        $clustersWithMoreThanLimitPlaces = $this->fetchClustersWithMoreThanLimitPlaces();

        $q = $this->connection->prepare('select canonical from duplicate_places where place_uuid = :place_uuid and cluster_id = :cluster_id');

        foreach ($placesFromCsv as $placeFromCsv) {
            if (!isset($clustersWithMoreThanLimitPlaces[$placesFromCsv['cluster_id']]) {
                continue; // Less than 50 places in this cluster
            }

            $result = $q->executeQuery(['place_uuid' => $placesFromCsv['cluster_id'], $placesFromCsv['cluster_id']]);
            $data = $result->fetchAssociative();

            if ($result->rowCount() === 0) {
                $msg = sprintf('The combination place uuid %s and cluster id %d is not valid', $placesFromCsv['place_uuid'], $placesFromCsv['cluster_id']);
                $output->writeln($msg);
                $this->logger->info($msg);
                continue;
            }

            if ($data['canonical']) {
                $msg = sprintf('Place %s is the canonical, will not delete', $placesFromCsv['place_uuid']);
                $output->writeln($msg);
                $this->logger->info($msg);
                continue;
            }
        }

        $msg = sprintf('Place %s is marked for deletion', $placesFromCsv['place_uuid']);
        $output->writeln($msg);
        $this->logger->info($msg);

        if (!$input->getOption(self::DRY_RUN)) {
            $this->commandBus->dispatch(
                new DeleteOffer($placesFromCsv['place_uuid'])
            );
        }
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, int $count): bool
    {
        if ($input->getOption(self::FORCE)) {
            return true;
        }

        return $this
            ->getHelper('question')
            ->ask(
                $input,
                $output,
                new ConfirmationQuestion(
                    "This action will queue {$count} places for deletion, continue? [y/N] ",
                    true
                )
            );
    }

    private function fetchClustersWithMoreThanLimitPlaces(): array
    {
        $q = $this->connection->prepare('select count(place_uuid) as total, cluster_id from duplicate_places group by cluster_id having total >= ' . self::MIN_SIZE_CLUSTER);
        $result = $q->executeQuery();
        $data = $result->fetchAllAssociative();

        $output = [];

        foreach ($data as $row) {
            $output[] = $row['cluster_id'];
        }

        return $output;
    }
}