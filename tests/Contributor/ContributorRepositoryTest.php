<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Contributor;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Web\EmailAddress;
use PHPUnit\Framework\TestCase;

final class ContributorRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private UUID $brusselsEvent;

    private UUID $ghentEvent;

    private ContributorRepository $contributorRepository;

    public function setUp(): void
    {
        $contributorRelationsTableName = 'contributor_relations';

        $this->brusselsEvent = new UUID('22d25373-259f-4abc-9b2a-93b1777cf4da');

        $this->ghentEvent = new UUID('9e4c6ef8-3bbc-45ab-9828-c621f781c978');

        $this->createTable(
            ContributorRelationsConfigurator::getTableDefinition(
                $this->createSchema()
            )
        );

        $this->getConnection()->insert(
            $contributorRelationsTableName,
            [
                'uuid' => $this->brusselsEvent->toString(),
                'email' => 'an@brussel.be',
            ]
        );
        $this->getConnection()->insert(
            $contributorRelationsTableName,
            [
                'uuid' => $this->brusselsEvent->toString(),
                'email' => 'piet@brussel.be',
            ]
        );
        $this->getConnection()->insert(
            $contributorRelationsTableName,
            [
                'uuid' => $this->brusselsEvent->toString(),
                'email' => 'info@brussel.be',
            ]
        );
        $this->getConnection()->insert(
            $contributorRelationsTableName,
            [
                'uuid' => $this->ghentEvent->toString(),
                'email' => 'info@gent.be',
            ]
        );

        $this->contributorRepository = new ContributorRepository(
            $this->getConnection()
        );
    }

    /**
     * @test
     */
    public function it_gets_the_contributors_of_an_item(): void
    {
        $this->assertEquals(
            [
                new EmailAddress('an@brussel.be'),
                new EmailAddress('piet@brussel.be'),
                new EmailAddress('info@brussel.be'),
            ],
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
     */
    public function it_can_add_a_contributor(): void
    {
        $this->contributorRepository->addContributor(
            $this->ghentEvent,
            new EmailAddress('vragen@gent.be')
        );

        $this->assertEquals(
            [
                new EmailAddress('info@gent.be'),
                new EmailAddress('vragen@gent.be'),
            ],
            $this->contributorRepository->getContributors($this->ghentEvent)
        );
    }

    /**
     * @test
     */
    public function it_can_delete_contributors(): void
    {
        $this->contributorRepository->deleteContributors($this->brusselsEvent);

        $this->assertEquals(
            [],
            $this->contributorRepository->getContributors($this->brusselsEvent)
        );
    }
}
