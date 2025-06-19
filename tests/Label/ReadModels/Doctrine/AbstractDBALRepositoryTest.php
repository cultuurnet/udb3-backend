<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\ReadModels\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractDBALRepositoryTest extends TestCase
{
    private Connection $connection;

    private string $tableName;

    private AbstractDBALRepository&MockObject $abstractDBALRepository;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(
            [
                'url' => 'sqlite:///:memory:',
            ]
        );

        $this->tableName = 'tableName';

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
    public function it_stores_a_connection(): void
    {
        $this->assertEquals(
            $this->connection,
            $this->abstractDBALRepository->getConnection()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_table_name(): void
    {
        $this->assertEquals(
            $this->tableName,
            $this->abstractDBALRepository->getTableName()
        );
    }

    /**
     * @test
     */
    public function it_creates_a_query_builder(): void
    {
        $this->assertNotNull(
            $this->abstractDBALRepository->createQueryBuilder()
        );
    }
}
