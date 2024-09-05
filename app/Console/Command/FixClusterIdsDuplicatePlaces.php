<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Console\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/*
 * @todo This is a temporary script and can probably be removed somewhere in 2025
 * */
class FixClusterIdsDuplicatePlaces extends BaseCommand
{
    private Connection $connection;

    public function __construct(
        Connection $connection
    ) {
        parent::__construct();

        $this->connection = $connection;
    }

    public function configure(): void
    {
        $this
            ->setName('place:duplicate-places:fix-cluster-ids')
            ->setDescription('Convert the cluster ids from integers to the new sha1 hashes')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): ?int
    {
        // Using SQL to retrieve the new cluster_id for each group using SHA1 and GROUP_CONCAT
        // Doctrine Query Builder cannot handle this complex scenario
        $sql = "SELECT cluster_id, 
                           SHA1(GROUP_CONCAT(place_uuid ORDER BY place_uuid ASC SEPARATOR '')) AS new_cluster_id
                    FROM duplicate_places
                    GROUP BY cluster_id";

        $stmt = $this->connection->executeQuery($sql);
        $results = $stmt->fetchAllAssociative();

        $count = 0;
        foreach ($results as $row) {
            if ($row['cluster_id'] === $row['new_cluster_id']) {
                continue;
            }

            $this->updateClusterId($row['cluster_id'], $row['new_cluster_id']);
            $count++;
        }
        $output->writeln(sprintf('%s clusters have been updated.', $count));

        return self::SUCCESS;
    }

    private function updateClusterId(string $oldClusterId, string $newClusterId): void
    {
        $this->connection->createQueryBuilder()
            ->update('duplicate_places', 'dp')
            ->set('dp.cluster_id', ':newClusterId')
            ->where('dp.cluster_id = :oldClusterId')
            ->setParameter('newClusterId', $newClusterId)
            ->setParameter('oldClusterId', $oldClusterId)
            ->execute();
    }
}
