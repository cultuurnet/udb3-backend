<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Metadata;

use CultuurNet\UDB3\DBALTestConnectionTrait;
use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Offer\ReadModel\Metadata\Doctrine\OfferMetadataSchemaConfigurator;
use PHPUnit\Framework\TestCase;

class OfferMetadataRepositoryTest extends TestCase
{
    use DBALTestConnectionTrait;

    /**
     * @var OfferMetadataRepository
     */
    private $repository;

    protected function setUp(): void
    {
        $schema = $this->createSchema();
        $this->createTable(
            OfferMetadataSchemaConfigurator::getTableDefinition($schema)
        );

        $this->repository = new OfferMetadataRepository($this->getConnection());
    }

    /**
     * @test
     */
    public function it_can_persist_offer_metadata(): void
    {
        $offerId = 'offer_id';
        $createdByApiConsumer = 'uitdatabank-ui';

        $offerMetadata = new OfferMetadata($offerId, $createdByApiConsumer);
        $this->repository->save($offerMetadata);

        $persistedOfferMetadata = $this->repository->get($offerId);
        $this->assertEquals($createdByApiConsumer, $persistedOfferMetadata->getCreatedByApiConsumer());
        $this->assertEquals($offerId, $persistedOfferMetadata->getOfferId());
    }

    /**
     * @test
     */
    public function it_will_throw_when_nothing_is_found(): void
    {
        $this->expectException(EntityNotFoundException::class);
        $this->repository->get('offer_id');
    }
}
