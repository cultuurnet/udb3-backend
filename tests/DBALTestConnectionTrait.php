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
    private array $connectionConfiguration;

    public function tearDown(): void
    {
        $this->recreateDatabase();
    }

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

        $configFile = __DIR__ . '/../config.php';
        $configuration = file_exists($configFile) ? (include $configFile)['database'] : [
            'driver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'user' => 'vagrant',
            'password' => 'vagrant',
        ];

        $this->connectionConfiguration = array_merge($configuration, [
            'dbname' => 'udb3_test',
        ]);

        $this->connection = DriverManager::getConnection(
            $this->connectionConfiguration
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

    public function recreateDatabase(): void
    {
        $this->getConnection()->getSchemaManager()->dropAndCreateDatabase($this->connectionConfiguration['dbname']);
    }
}
