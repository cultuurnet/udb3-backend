<?php

namespace CultuurNet\UDB3;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PDO;

trait DBALTestConnectionTrait
{
    /**
     * @var Connection
     */
    private $connection;

    protected function initializeConnection()
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

    public function getConnection()
    {
        if (!$this->connection) {
            $this->initializeConnection();
        }

        return $this->connection;
    }
}
