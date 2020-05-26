<?php

namespace CultuurNet\UDB3\Label\ReadModels\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class AbstractDBALRepositoryTest extends TestCase
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var StringLiteral
     */
    private $tableName;

    /**
     * @var AbstractDBALRepository
     */
    private $abstractDBALRepository;

    protected function setUp()
    {
        $this->connection = DriverManager::getConnection(
            [
                'url' => 'sqlite:///:memory:',
            ]
        );

        $this->tableName = new StringLiteral('tableName');

        $this->abstractDBALRepository = $this->getMockForAbstractClass(
            AbstractDBALRepository::class,
            [
                $this->connection,
                $this->tableName,
            ]
        );
    }

    /**
     * @test
     */
    public function it_stores_a_connection()
    {
        $this->assertEquals(
            $this->connection,
            $this->abstractDBALRepository->getConnection()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_table_name()
    {
        $this->assertEquals(
            $this->tableName,
            $this->abstractDBALRepository->getTableName()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_query_builder()
    {
        $this->assertNotNull(
            $this->abstractDBALRepository->createQueryBuilder()
        );
    }
}
