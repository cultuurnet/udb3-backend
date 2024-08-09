<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Recommendations;

use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class RecommendationForEnrichedOfferRepositoryTest extends TestCase
{
    /** @var RecommendationsRepository&MockObject  */
    private $recommendationsRepository;

    private InMemoryDocumentRepository $offerRepository;

    private RecommendationForEnrichedOfferRepository $recommendationForEnrichedOfferRepository;

    protected function setUp(): void
    {
        $this->recommendationsRepository = $this->createMock(RecommendationsRepository::class);

        $this->offerRepository = new InMemoryDocumentRepository();

        $this->recommendationForEnrichedOfferRepository = new RecommendationForEnrichedOfferRepository(
            $this->recommendationsRepository,
            new CallableIriGenerator(
                function (string $eventId): string {
                    return 'https://io.uitdatabank.be/events/' . $eventId;
                }
            ),
            $this->offerRepository
        );
    }

    /**
     * @test
     */
    public function it_enriches_with_recommendations(): void
    {
        $offerId = '4ff559bd-9543-4ae2-900f-fe6d32fd019b';

        $jsonLd = new JsonDocument($offerId, Json::encode(['@type' => 'Event']));
        $this->offerRepository->save($jsonLd);

        $this->recommendationsRepository->expects($this->once())
            ->method('getByRecommendedEvent')
            ->with($offerId)
            ->willReturn(
                new Recommendations(
                    new Recommendation('1', 0.1),
                    new Recommendation('2', 0.2)
                )
            );

        $fetchJsonLd = $this->recommendationForEnrichedOfferRepository->fetch($offerId, true);

        $expectedJsonLd = new JsonDocument(
            $offerId,
            Json::encode(
                [
                    '@type' => 'Event',
                    'metadata' => [
                        'recommendationFor' => [
                            [
                                'event' => 'https://io.uitdatabank.be/events/1',
                                'score' => 0.1,
                            ],
                            [
                                'event' => 'https://io.uitdatabank.be/events/2',
                                'score' => 0.2,
                            ],
                        ],
                    ],
                ]
            )
        );

        $this->assertEquals($expectedJsonLd, $fetchJsonLd);
    }

    /**
     * @test
     */
    public function it_does_not_enrich_when_no_recommendations(): void
    {
        $offerId = '4ff559bd-9543-4ae2-900f-fe6d32fd019b';

        $jsonLd = new JsonDocument($offerId, Json::encode(['@type' => 'Event']));
        $this->offerRepository->save($jsonLd);

        $this->recommendationsRepository->expects($this->once())
            ->method('getByRecommendedEvent')
            ->with($offerId)
            ->willReturn(
                new Recommendations()
            );

        $this->recommendationsRepository->expects($this->once())
            ->method('getByRecommendedEvent')
            ->with($offerId)
            ->willReturn(
                new Recommendations()
            );

        $fetchJsonLd = $this->recommendationForEnrichedOfferRepository->fetch($offerId, true);

        $this->assertEquals($jsonLd, $fetchJsonLd);
    }

    /**
     * @test
     */
    public function it_does_not_enrich_when_no_metadata_asked(): void
    {
        $offerId = '4ff559bd-9543-4ae2-900f-fe6d32fd019b';

        $jsonLd = new JsonDocument($offerId, Json::encode(['@type' => 'Event']));
        $this->offerRepository->save($jsonLd);

        $this->recommendationsRepository->expects($this->never())
            ->method('getByRecommendedEvent');

        $fetchJsonLd = $this->recommendationForEnrichedOfferRepository->fetch($offerId);

        $this->assertEquals($jsonLd, $fetchJsonLd);
    }

    /**
     * @test
     */
    public function it_throws_when_offer_was_not_found(): void
    {
        $offerId = '4ff559bd-9543-4ae2-900f-fe6d32fd019b';

        $this->expectException(DocumentDoesNotExist::class);

        $this->recommendationForEnrichedOfferRepository->fetch($offerId, true);
    }
}
