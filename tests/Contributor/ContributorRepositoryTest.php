<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\ItemType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddresses;
use CultuurNet\UDB3\Offer\OfferType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\TestCase;

final class ContributorRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private UUID $brusselsEvent;

    private UUID $ghentEvent;

    private DbalContributorRepository $contributorRepository;

    private static function getTableDefinition(Schema $schema): Table
    {
        $table = $schema->createTable('contributor_relations');

        $table->addColumn('uuid', Type::GUID)->setLength(36)->setNotnull(true);
        $table->addColumn('email', Type::TEXT)->setNotnull(true);
        $table->addColumn('type', Type::STRING)->setLength(255)->setNotnull(true);

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
                'type' => OfferType::event()->toString(),
            ]
        );
        $this->getConnection()->insert(
            $contributorRelationsTableName,
            [
                'uuid' => $this->brusselsEvent->toString(),
                'email' => 'piet@brussel.be',
                'type' => OfferType::event()->toString(),
            ]
        );
        $this->getConnection()->insert(
            $contributorRelationsTableName,
            [
                'uuid' => $this->brusselsEvent->toString(),
                'email' => 'info@brussel.be',
                'type' => OfferType::event()->toString(),
            ]
        );
        $this->getConnection()->insert(
            $contributorRelationsTableName,
            [
                'uuid' => $this->ghentEvent->toString(),
                'email' => 'info@gent.be',
                'type' => OfferType::event()->toString(),
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
     * @dataProvider itemProvider
     */
    public function it_can_overwrite_contributor(ItemType $itemType): void
    {
        $this->contributorRepository->overwriteContributors(
            $this->ghentEvent,
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
            $this->contributorRepository->getContributors($this->ghentEvent)
        );
    }

    public function itemProvider(): array
    {
        return [
            [ItemType::event()],
            //[ItemType::place()],
            //[ItemType::organizer()],
        ];
    }
}
