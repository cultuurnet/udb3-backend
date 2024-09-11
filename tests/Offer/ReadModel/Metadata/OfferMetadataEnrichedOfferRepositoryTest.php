<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\Metadata;

use CultuurNet\UDB3\EntityNotFoundException;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OfferMetadataEnrichedOfferRepositoryTest extends TestCase
{
    /**
     * @var MockObject
     */
    private $offerMetadataRepository;

    private InMemoryDocumentRepository $decoratedRepository;

    private OfferMetadataEnrichedOfferRepository $offerMetadataEnrichedOfferRepository;

    protected function setUp(): void
    {
        $this->offerMetadataRepository = $this->createMock(OfferMetadataRepository::class);
        $this->decoratedRepository = new InMemoryDocumentRepository();

        $this->offerMetadataEnrichedOfferRepository = new OfferMetadataEnrichedOfferRepository(
            $this->offerMetadataRepository,
            $this->decoratedRepository
        );
    }

    /**
     * @test
     */
    public function it_does_not_add_offer_metadata_if_include_metadata_is_false(): void
    {
        $offerId = '4ff559bd-9543-4ae2-900f-fe6d32fd019b';

        $jsonLd = new JsonDocument($offerId, Json::encode(['@type' => 'Event']));
        $this->decoratedRepository->save($jsonLd);

        $fetchJsonLd = $this->offerMetadataEnrichedOfferRepository->fetch($offerId, false);

        $this->assertEquals($jsonLd, $fetchJsonLd);
    }

    /**
     * @test
     */
    public function it_can_add_offer_metadata(): void
    {
        $offerId = '4ff559bd-9543-4ae2-900f-fe6d32fd019b';

        $this->offerMetadataRepository->method('get')->willReturn(
            new OfferMetadata($offerId, 'uitdatabank-ui')
        );

        $jsonLd = new JsonDocument($offerId, Json::encode(['@type' => 'Event']));
        $this->decoratedRepository->save($jsonLd);

        $fetchJsonLd = $this->offerMetadataEnrichedOfferRepository->fetch($offerId, true);

        $expectedJsonLd = new JsonDocument(
            $offerId,
            Json::encode(
                [
                    '@type' => 'Event',
                    'metadata' => [
                        'createdByApiConsumer' => 'uitdatabank-ui',
                    ],
                ]
            )
        );

        $this->assertEquals($expectedJsonLd, $fetchJsonLd);
    }

    /**
     * @test
     */
    public function it_adds_default_metadata_when_not_found(): void
    {
        $offerId = '4ff559bd-9543-4ae2-900f-fe6d32fd019b';

        $this->offerMetadataRepository->method('get')->willThrowException(
            new EntityNotFoundException()
        );

        $jsonLd = new JsonDocument($offerId, Json::encode(['@type' => 'Event']));
        $this->decoratedRepository->save($jsonLd);

        $fetchJsonLd = $this->offerMetadataEnrichedOfferRepository->fetch($offerId, true);

        $expectedJsonLd = new JsonDocument(
            $offerId,
            Json::encode(
                [
                    '@type' => 'Event',
                    'metadata' => [
                        'createdByApiConsumer' => 'unknown',
                    ],
                ]
            )
        );

        $this->assertEquals($expectedJsonLd, $fetchJsonLd);
    }
}
