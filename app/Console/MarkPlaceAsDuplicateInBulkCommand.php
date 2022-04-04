<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Silex\Console;

use Broadway\CommandHandling\CommandBus;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Place\CannotMarkPlaceAsCanonical;
use CultuurNet\UDB3\Place\CannotMarkPlaceAsDuplicate;
use CultuurNet\UDB3\Place\Commands\MarkAsDuplicate;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Psr\Log\LoggerAwareInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class MarkPlaceAsDuplicateInBulkCommand extends AbstractCommand
{
    private EventListener $processManager;

    private Connection $connection;

    public function __construct(CommandBus $commandBus, Connection $connection, EventListener $processManager)
    {
        parent::__construct($commandBus);
        $this->connection = $connection;
        $this->processManager = $processManager;
    }

    public function configure(): void
    {
        $this->setName('place:mark-as-duplicate-bulk');
        $this->setDescription('Marks multiple Places as duplicate of another one, implicitly making that a canonical');
      }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $output->setVerbosity(OutputInterface::VERBOSITY_VERY_VERBOSE);
        $logger = new ConsoleLogger($output);

        if ($this->processManager instanceof LoggerAwareInterface) {
            $this->processManager->setLogger($logger);
        }

        $clusterIds = $this->getClusterIds();

        foreach ($clusterIds as $clusterId) {
            $placeIds = $this->getPlaceIds($clusterId);
            // TODO
            $duplicateIds = $this->getDuplicates($placeIds);
            $canonicalId = $this->getCanonicalId($placeIds);
            // END TODO
            foreach ($duplicateIds as $duplicateId) {

                try {
                    $this->commandBus->dispatch(
                        new MarkAsDuplicate(
                            $duplicateId,
                            $canonicalId
                        )
                    );
                    $logger->info('Successfully marked place as duplicate');
                } catch (CannotMarkPlaceAsCanonical | CannotMarkPlaceAsDuplicate $e) {
                    $logger->error($e->getMessage());
                    return 1;
                }
            }
        }
        return 0;
    }

    private function getClusterIds(): array
    {
        return $this->connection->createQueryBuilder()
            ->select('DISTINCT cluster_id')
            ->from('duplicate_places')
            ->execute()
            ->fetchAll(FetchMode::COLUMN);
    }

    private function getPlaceIds(int $clusterId): array
    {
        return $this->connection->createQueryBuilder()
            ->select('place_uuid')
            ->from('duplicate_places')
            ->where('cluster_id = :clusterId')
            ->setParameter(':clusterId', $clusterId)
            ->execute()
            ->fetchAll(FetchMode::COLUMN);
    }

    private function getDuplicates(array $placeIds): array
    {
        return [];
    }

    private function getCanonicalId(array $placeIds): string
    {
        return '';
    }
}
