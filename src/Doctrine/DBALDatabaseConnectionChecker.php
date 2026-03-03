<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\Log\LoggerInterface;

final class DBALDatabaseConnectionChecker implements DatabaseConnectionChecker
{
    public function __construct(
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function ensureConnection(): void
    {
        try {
            $this->connection->executeQuery('SELECT 1');
            $this->logger->debug('Connection to database successfully verified');
        } catch (Exception $exception) {
            $this->logger->warning('Database connection lost, reconnecting...', [
                'exception_message' => $exception->getMessage(),
            ]);

            try {
                $this->connection->close();
                $this->connection->connect();
                $this->logger->debug('Successfully reconnected to database');
            } catch (Exception $exception) {
                $this->logger->critical('Failed to reconnect to database', [
                    'exception_message' => $exception->getMessage(),
                ]);
            }
        }
    }
}
