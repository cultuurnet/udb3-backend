<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use PHPUnit\Framework\TestCase;
use CultuurNet\UDB3\StringLiteral;

class DBALResourceOwnerRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var DBALResourceOwnerRepository
     */
    private $repository;

    public function setUp(): void
    {
        $table = new StringLiteral('event_permission');
        $idField = new StringLiteral('event_id');

        (new SchemaConfigurator($table, $idField))->configure(
            $this->getConnection()->getSchemaManager()
        );

        $this->repository = new DBALResourceOwnerRepository(
            $table,
            $this->getConnection(),
            $idField
        );
    }

    /**
     * @test
     */
    public function it_can_add_and_query_offer_permissions(): void
    {
        $johnDoe = 'abc';
        $editableByJohnDoe = [
            new StringLiteral('123'),
            new StringLiteral('456'),
            new StringLiteral('789'),
        ];
        $janeDoe = 'def';
        $editableByJaneDoe = [
            new StringLiteral('101112'),
            new StringLiteral('131415'),
            new StringLiteral('456'),
        ];

        $this->assertEquals(
            [],
            $this->repository->getEditableResourceIds($johnDoe)
        );

        $this->assertEquals(
            [],
            $this->repository->getEditableResourceIds($janeDoe)
        );

        array_walk($editableByJohnDoe, [$this, 'markEditable'], new StringLiteral($johnDoe));
        array_walk($editableByJaneDoe, [$this, 'markEditable'], new StringLiteral($janeDoe));

        $this->assertEquals(
            $editableByJohnDoe,
            $this->repository->getEditableResourceIds($johnDoe)
        );

        $this->assertEquals(
            $editableByJaneDoe,
            $this->repository->getEditableResourceIds($janeDoe)
        );
    }

    private function markEditable(string $eventId, string $key, string $userId): void
    {
        $this->repository->markResourceEditableByUser($eventId, $userId);
    }

    /**
     * @test
     */
    public function it_silently_ignores_adding_duplicate_permissions(): void
    {
        $johnDoe = 'abc';
        $editableByJohnDoe = [
            new StringLiteral('123'),
            new StringLiteral('456'),
            new StringLiteral('789'),
        ];

        array_walk($editableByJohnDoe, [$this, 'markEditable'], $johnDoe);

        $this->repository->markResourceEditableByUser('456', $johnDoe);

        $this->assertEquals(
            $editableByJohnDoe,
            $this->repository->getEditableResourceIds($johnDoe)
        );
    }

    /**
     * @test
     */
    public function it_updates_the_user_id_if_explicitly_requested(): void
    {
        $johnDoe = 'abc';
        $janeDoe = 'def';
        $editableByJohnDoe = [
            new StringLiteral('123'),
            new StringLiteral('456'),
            new StringLiteral('789'),
        ];

        array_walk($editableByJohnDoe, [$this, 'markEditable'], new StringLiteral($johnDoe));

        $this->repository->markResourceEditableByNewUser(new StringLiteral('456'), new StringLiteral($janeDoe));

        $this->assertEquals(
            [
                new StringLiteral('456'),
            ],
            $this->repository->getEditableResourceIds($janeDoe)
        );
        $this->assertEquals(
            [
                new StringLiteral('123'),
                new StringLiteral('789'),
            ],
            $this->repository->getEditableResourceIds($johnDoe)
        );
    }
}
