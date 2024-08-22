<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventSourcing\DBAL;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainEventStream;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Serializer\Serializer;
use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\AggregateType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UniqueDBALEventStoreDecoratorTest extends TestCase
{
    use DBALTestConnectionTrait;

    public const ID = 'id';
    public const UNIQUE_VALUE = 'unique';
    public const OTHER_ID = 'otherId';
    public const OTHER_UNIQUE_VALUE = 'otherUnique';

    private UniqueDBALEventStoreDecorator $uniqueDBALEventStoreDecorator;

    /**
     * @var UniqueConstraintService&MockObject
     */
    private $uniqueConstraintService;

    private string $uniqueTableName;

    protected function setUp(): void
    {
        $this->uniqueTableName = 'labels_unique';

        $this->setUpDatabase(false, ['event_store', 'labels_unique']);

        $serializer = $this->createMock(Serializer::class);

        /** @var AggregateAwareDBALEventStore&MockObject $dbalEventStore */
        $dbalEventStore = $this
            ->getMockBuilder(AggregateAwareDBALEventStore::class)
            ->setConstructorArgs([$this->getConnection(), $serializer, $serializer, 'event_store', AggregateType::event()])
            ->enableProxyingToOriginalMethods()
            ->getMock();

        $this->uniqueConstraintService = $this->createMock(UniqueConstraintService::class);

        $this->uniqueConstraintService->expects($this->any())
            ->method('hasUniqueConstraint')
            ->willReturn(true);

        $this->uniqueConstraintService->expects($this->any())
            ->method('getUniqueConstraintValue')
            ->willReturn(self::UNIQUE_VALUE);

        $this->uniqueDBALEventStoreDecorator = new UniqueDBALEventStoreDecorator(
            $dbalEventStore,
            $this->connection,
            $this->uniqueTableName,
            $this->uniqueConstraintService
        );
    }

    /**
     * @test
     */
    public function it_can_append_domain_messages_with_a_unique_value_if_the_unique_value_has_not_been_used_before(): void
    {
        $this->insert(self::OTHER_ID, self::OTHER_UNIQUE_VALUE);

        $domainMessage = new DomainMessage(
            self::ID,
            0,
            new Metadata(),
            new \stdClass(),
            BroadwayDateTime::now()
        );

        $this->uniqueDBALEventStoreDecorator->append(
            $domainMessage->getId(),
            new DomainEventStream([$domainMessage])
        );

        $unique = $this->select(self::ID);

        $this->assertEquals(self::UNIQUE_VALUE, $unique);
    }

    /**
     * @test
     */
    public function it_does_not_append_domain_messages_with_a_unique_value_if_the_unique_value_has_been_used_before(): void
    {
        $this->insert(self::OTHER_ID, self::UNIQUE_VALUE);

        $domainMessage = new DomainMessage(
            self::ID,
            0,
            new Metadata(),
            new \stdClass(),
            BroadwayDateTime::now()
        );

        try {
            $this->uniqueDBALEventStoreDecorator->append(
                $domainMessage->getId(),
                new DomainEventStream([$domainMessage])
            );
            $this->fail('Did not throw expected UniqueConstraintException.');
        } catch (\Exception $e) {
            $this->assertInstanceOf(UniqueConstraintException::class, $e);
            $this->assertEquals(
                'Not unique: uuid = ' . self::ID . ', duplicate value = ' . self::UNIQUE_VALUE,
                $e->getMessage()
            );

            // Make sure no events were appended to the event store.
            $rowCountResult = $this->connection->createQueryBuilder()
                ->select('count(*) as total')
                ->from('event_store')
                ->execute()
                ->fetch();

            $rowCount = $rowCountResult['total'];

            $this->assertEquals(0, $rowCount);
        }
    }

    /**
     * @test
     */
    public function it_can_update_a_unique_value_when_the_new_value_has_not_yet_been_used(): void
    {
        $this->insert(self::ID, self::OTHER_UNIQUE_VALUE);

        $domainMessage = new DomainMessage(
            self::ID,
            0,
            new Metadata(),
            new \stdClass(),
            BroadwayDateTime::now()
        );

        $this->uniqueConstraintService->expects($this->any())
            ->method('needsUpdateUniqueConstraint')
            ->willReturn(true);

        $this->uniqueDBALEventStoreDecorator->append(
            $domainMessage->getId(),
            new DomainEventStream([$domainMessage])
        );

        $unique = $this->select(self::ID);

        $this->assertEquals(self::UNIQUE_VALUE, $unique);
    }

    /**
     * @test
     */
    public function it_does_not_update_a_unique_value_when_the_new_value_has_already_been_used(): void
    {
        $this->insert(self::ID, self::OTHER_UNIQUE_VALUE);
        $this->insert(self::OTHER_ID, self::UNIQUE_VALUE);

        $domainMessage = new DomainMessage(
            self::ID,
            0,
            new Metadata(),
            new \stdClass(),
            BroadwayDateTime::now()
        );

        $this->uniqueConstraintService->expects($this->any())
            ->method('needsUpdateUniqueConstraint')
            ->willReturn(true);

        $this->expectException(UniqueConstraintException::class);

        $this->uniqueDBALEventStoreDecorator->append(
            $domainMessage->getId(),
            new DomainEventStream([$domainMessage])
        );
    }

    /**
     * @test
     */
    public function it_does_not_insert_when_preflight_lookup_throws(): void
    {
        $this->insert(self::ID, self::UNIQUE_VALUE);

        $this->uniqueConstraintService->expects($this->once())
            ->method('needsPreflightLookup')
            ->willReturn(true);

        $this->uniqueConstraintService->expects($this->never())
            ->method('needsUpdateUniqueConstraint');

        $this->expectException(UniqueConstraintException::class);

        $domainMessage = new DomainMessage(
            self::ID,
            0,
            new Metadata(),
            new \stdClass(),
            BroadwayDateTime::now()
        );

        $this->uniqueDBALEventStoreDecorator->append(
            $domainMessage->getId(),
            new DomainEventStream([$domainMessage])
        );
    }

    /**
     * @test
     * @see https://jira.uitdatabank.be/browse/III-4078
     * When `aunique` and `bunique` are already added as unique values, adding `unique` should still work
     */
    public function it_does_not_fail_on_preflight_with_partial_match(): void
    {
        $this->insert('1', 'aunique');
        $this->insert('2', 'bunique');

        $this->uniqueConstraintService->expects($this->once())
            ->method('needsPreflightLookup')
            ->willReturn(true);

        $this->uniqueConstraintService->expects($this->never())
            ->method('needsUpdateUniqueConstraint');

        $domainMessage = new DomainMessage(
            self::ID,
            0,
            new Metadata(),
            new \stdClass(),
            BroadwayDateTime::now()
        );

        $this->uniqueDBALEventStoreDecorator->append(
            $domainMessage->getId(),
            new DomainEventStream([$domainMessage])
        );
    }

    private function insert(string $uuid, string $unique): void
    {
        $sql = 'INSERT INTO ' . $this->uniqueTableName . ' VALUES (?, ?)';

        $this->connection->executeQuery($sql, [$uuid, $unique]);
    }

    private function select(string $uuid): string
    {
        $tableName = $this->uniqueTableName;
        $where = ' WHERE ' . UniqueDBALEventStoreDecorator::UUID_COLUMN . ' = ?';

        $sql = 'SELECT * FROM ' . $tableName . $where;

        $statement = $this->connection->executeQuery($sql, [$uuid]);
        $rows = $statement->fetchAllAssociative();

        return $rows[0][UniqueDBALEventStoreDecorator::UNIQUE_COLUMN];
    }
}
