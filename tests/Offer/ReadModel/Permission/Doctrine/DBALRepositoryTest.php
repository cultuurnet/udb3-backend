<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Permission\Doctrine;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use PHPUnit\Framework\TestCase;
use ValueObjects\StringLiteral\StringLiteral;

class DBALRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var DBALRepository
     */
    private $repository;

    public function setUp()
    {
        $table = new StringLiteral('event_permission');
        $idField = new StringLiteral('event_id');

        (new SchemaConfigurator($table, $idField))->configure(
            $this->getConnection()->getSchemaManager()
        );

        $this->repository = new DBALRepository(
            $table,
            $this->getConnection(),
            $idField
        );
    }

    /**
     * @test
     */
    public function it_can_add_and_query_offer_permissions()
    {
        $johnDoe = new StringLiteral('abc');
        $editableByJohnDoe = [
            new StringLiteral('123'),
            new StringLiteral('456'),
            new StringLiteral('789'),
        ];
        $janeDoe = new StringLiteral('def');
        $editableByJaneDoe = [
            new StringLiteral('101112'),
            new StringLiteral('131415'),
            new StringLiteral('456'),
        ];

        $this->assertEquals(
            [],
            $this->repository->getEditableOffers($johnDoe)
        );

        $this->assertEquals(
            [],
            $this->repository->getEditableOffers($janeDoe)
        );

        array_walk($editableByJohnDoe, [$this, 'markEditable'], $johnDoe);
        array_walk($editableByJaneDoe, [$this, 'markEditable'], $janeDoe);

        $this->assertEquals(
            $editableByJohnDoe,
            $this->repository->getEditableOffers($johnDoe)
        );

        $this->assertEquals(
            $editableByJaneDoe,
            $this->repository->getEditableOffers($janeDoe)
        );
    }

    /**
     * @param string $key
     */
    private function markEditable(StringLiteral $eventId, $key, StringLiteral $userId)
    {
        $this->repository->markOfferEditableByUser($eventId, $userId);
    }

    /**
     * @test
     */
    public function it_silently_ignores_adding_duplicate_permissions()
    {
        $johnDoe = new StringLiteral('abc');
        $editableByJohnDoe = [
            new StringLiteral('123'),
            new StringLiteral('456'),
            new StringLiteral('789'),
        ];

        array_walk($editableByJohnDoe, [$this, 'markEditable'], $johnDoe);

        $this->repository->markOfferEditableByUser(new StringLiteral('456'), $johnDoe);

        $this->assertEquals(
            $editableByJohnDoe,
            $this->repository->getEditableOffers($johnDoe)
        );
    }

    /**
     * @test
     */
    public function it_updates_the_user_id_if_explicitly_requested(): void
    {
        $johnDoe = new StringLiteral('abc');
        $janeDoe = new StringLiteral('def');
        $editableByJohnDoe = [
            new StringLiteral('123'),
            new StringLiteral('456'),
            new StringLiteral('789'),
        ];

        array_walk($editableByJohnDoe, [$this, 'markEditable'], $johnDoe);

        $this->repository->markOfferEditableByNewUser(new StringLiteral('456'), $janeDoe);

        $this->assertEquals(
            [
                new StringLiteral('456'),
            ],
            $this->repository->getEditableOffers($janeDoe)
        );
        $this->assertEquals(
            [
                new StringLiteral('123'),
                new StringLiteral('789'),
            ],
            $this->repository->getEditableOffers($johnDoe)
        );
    }
}
