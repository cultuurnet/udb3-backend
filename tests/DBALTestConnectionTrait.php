<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use PDO;

trait DBALTestConnectionTrait
{
    private ?Connection $connection = null;

    protected function initializeConnection(): void
    {
        if (!class_exists('PDO')) {
            $this->markTestSkipped('PDO is required to run this test.');
        }

        $availableDrivers = PDO::getAvailableDrivers();
        if (!in_array('sqlite', $availableDrivers)) {
            $this->markTestSkipped(
                'PDO sqlite driver is required to run this test.'
            );
        }

        $this->connection = DriverManager::getConnection(
            [
                'url' => 'sqlite:///:memory:',
            ]
        );
    }

    public function getConnection(): Connection
    {
        if (!$this->connection) {
            $this->initializeConnection();
        }

        return $this->connection;
    }

    public function createSchema(): Schema
    {
        return $this->getConnection()->getSchemaManager()->createSchema();
    }

    public function createTable(Table $table): void
    {
        $this->getConnection()->getSchemaManager()->createTable($table);
    }
}
