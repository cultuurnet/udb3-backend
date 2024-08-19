<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use PDO;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

trait DBALTestConnectionTrait
{
    private ?Connection $connection = null;
    private array $connectionConfiguration;

    public function setUpDatabase(): void
    {
        if (!$this->getConnection()->getSchemaManager()->tablesExist('duplicate_places')) {
            $this->runMigrations();
        }

        $this->getConnection()->beginTransaction();
    }

    public function setUp()
    {
        $this->setUpDatabase();
    }

    public function tearDown(): void
    {
        $this->getConnection()->rollBack();
        $this->getConnection()->close();
    }

    protected function initializeConnection(): void
    {
        if (!class_exists('PDO')) {
            $this->markTestSkipped('PDO is required to run this test.');
        }

        $availableDrivers = PDO::getAvailableDrivers();
        if (!in_array('mysql', $availableDrivers)) {
            $this->markTestSkipped(
                'PDO mysql driver is required to run this test.'
            );
        }

        $configFile = __DIR__ . '/../config.php';
        $configuration = file_exists($configFile) ? (include $configFile)['database'] : [
            'driver' => 'pdo_mysql',
            'host' => '127.0.0.1',
            'user' => 'vagrant',
            'password' => 'vagrant',
            'port' => getenv('DATABASE_PORT') ?: 3306,
        ];

        $this->connectionConfiguration = array_merge($configuration, ['dbname' => 'udb3_test']);
        $this->connection = DriverManager::getConnection($this->connectionConfiguration);

        $this->runMigrations();
    }

    protected function runMigrations(): void
    {
        $command = new MigrateCommand();
        $command->setApplication(new Application());
        $command->setConnection($this->connection);

        $input = new ArrayInput([]);
        $input->setInteractive(false);

        $command->run($input, new NullOutput());
    }

    public function getConnection(): Connection
    {
        if (!$this->connection) {
            $this->initializeConnection();
        }

        return $this->connection;
    }
}
