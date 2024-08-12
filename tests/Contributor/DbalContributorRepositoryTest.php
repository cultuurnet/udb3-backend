<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Types;
use PHPUnit\Framework\TestCase;

final class DbalContributorRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    public const TABLE_NAME = 'contributor_relations';

    private UUID $brusselsEvent;

    private UUID $ghentEvent;

    private DbalContributorRepository $contributorRepository;

    private static function getTableDefinition(Schema $schema): Table
    {
        $table = $schema->hasTable(self::TABLE_NAME) ? $schema->getTable(self::TABLE_NAME) : $schema->createTable(self::TABLE_NAME);

        $table->addColumn('uuid', Types::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('email', Types::TEXT)->setNotnull(true);
        $table->addColumn('type', Types::STRING)->setLength(255)->setNotnull(true);

        return $table;
    }

    public function setUp(): void
    {
        $contributorRelationsTableName = 'contributor_relations';

        $this->brusselsEvent = new UUID('22d25373-259f-4abc-9b2a-93b1777cf4da');

        $this->ghentEvent = new UUID('9e4c6ef8-3bbc-45ab-9828-c621f781c978');

        $this->createTable(
            self::getTableDefinition(
                $this->createSchema()
            )
        );

        $this->getConnection()->insert(
            $contributorRelationsTableName,
            [
                'uuid' => $this->brusselsEvent->toString(),
                'email' => 'an@brussel.be',
                'type' => ItemType::event()->toString(),
            ]
        );
        $this->getConnection()->insert(
            $contributorRelationsTableName,
            [
                'uuid' => $this->brusselsEvent->toString(),
                'email' => 'piet@brussel.be',
                'type' => ItemType::event()->toString(),
            ]
        );
        $this->getConnection()->insert(
            $contributorRelationsTableName,
            [
                'uuid' => $this->brusselsEvent->toString(),
                'email' => 'info@brussel.be',
                'type' => ItemType::event()->toString(),
            ]
        );
        $this->getConnection()->insert(
            $contributorRelationsTableName,
            [
                'uuid' => $this->ghentEvent->toString(),
                'email' => 'info@gent.be',
                'type' => ItemType::event()->toString(),
            ]
        );

        $this->contributorRepository = new DbalContributorRepository(
            $this->getConnection()
        );
    }

    /**
     * @test
     */
    public function it_gets_the_contributors_of_an_item(): void
    {
        $this->assertEquals(
            EmailAddresses::fromArray([
                new EmailAddress('an@brussel.be'),
                new EmailAddress('piet@brussel.be'),
                new EmailAddress('info@brussel.be'),
            ]),
            $this->contributorRepository->getContributors($this->brusselsEvent)
        );
    }

    /**
     * @test
     */
    public function it_checks_if_an_email_is_a_contributor(): void
    {
        $this->assertTrue(
            $this->contributorRepository->isContributor(
                $this->brusselsEvent,
                new EmailAddress('info@brussel.be')
            )
        );

        $this->assertFalse(
            $this->contributorRepository->isContributor(
                $this->ghentEvent,
                new EmailAddress('info@brussel.be')
            )
        );
    }

    /**
     * @test
     * @dataProvider itemTypeDataProvider
     */
    public function it_can_overwrite_contributor_and_type(ItemType $itemType): void
    {
        $newItem = new UUID('53dae0d5-c92f-4909-aa26-2be8dac23e69');
        $this->contributorRepository->updateContributors(
            $newItem,
            EmailAddresses::fromArray(
                [
                    new EmailAddress('pol@gent.be'),
                    new EmailAddress('mieke@gent.be'),
                ]
            ),
            $itemType
        );

        $this->assertEquals(
            EmailAddresses::fromArray([
                new EmailAddress('pol@gent.be'),
                new EmailAddress('mieke@gent.be'),
            ]),
            $this->contributorRepository->getContributors($newItem)
        );

        $result = $this->connection->createQueryBuilder()
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where('uuid = :uuid')
            ->setParameter(':uuid', $newItem->toString())
            ->andWhere('type = :type')
            ->setParameter(':type', $itemType->toString())
            ->execute()
            ->fetchAllAssociative();

        $this->assertEquals(2, count($result));
    }

    public function itemTypeDataProvider(): array
    {
        return [
            'event' => [
                ItemType::event(),
            ],
            'place' => [
                ItemType::place(),
            ],
            'organizer' => [
                ItemType::organizer(),
            ],
        ];
    }
}
