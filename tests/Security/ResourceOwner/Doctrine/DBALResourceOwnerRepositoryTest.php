<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Security\ResourceOwner\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use PHPUnit\Framework\TestCase;

final class DBALResourceOwnerRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    private DBALResourceOwnerRepository $repository;

    public function setUp(): void
    {
        $this->setUpDatabase();

        $table = 'event_permission_readmodel';
        $idField = 'event_id';

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
            '123',
            '456',
            '789',
        ];
        $janeDoe = 'def';
        $editableByJaneDoe = [
            '101112',
            '131415',
            '456',
        ];

        $this->assertEquals(
            [],
            $this->repository->getEditableResourceIds($johnDoe)
        );

        $this->assertEquals(
            [],
            $this->repository->getEditableResourceIds($janeDoe)
        );

        array_walk($editableByJohnDoe, [$this, 'markEditable'], $johnDoe);
        array_walk($editableByJaneDoe, [$this, 'markEditable'], $janeDoe);

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
            '123',
            '456',
            '789',
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
            '123',
            '456',
            '789',
        ];

        array_walk($editableByJohnDoe, [$this, 'markEditable'], $johnDoe);

        $this->repository->markResourceEditableByNewUser('456', $janeDoe);

        $this->assertEquals(
            [
                '456',
            ],
            $this->repository->getEditableResourceIds($janeDoe)
        );
        $this->assertEquals(
            [
                '123',
                '789',
            ],
            $this->repository->getEditableResourceIds($johnDoe)
        );
    }

    /**
     * @test
     */
    public function it_updates_the_user_id_if_previous_user_was_not_known(): void
    {
        $janeDoe = 'def';

        $this->repository->markResourceEditableByNewUser('C50051D6-EEB1-E9F9-B07889755901D716', $janeDoe);

        $this->assertEquals(
            [
                'C50051D6-EEB1-E9F9-B07889755901D716',
            ],
            $this->repository->getEditableResourceIds($janeDoe)
        );
    }
}
