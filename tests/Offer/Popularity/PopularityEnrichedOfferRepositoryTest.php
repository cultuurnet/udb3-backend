<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Popularity;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class PopularityEnrichedOfferRepositoryTest extends TestCase
{
    private InMemoryPopularityRepository $popularityRepository;

    private InMemoryDocumentRepository $decoratedRepository;

    private PopularityEnrichedOfferRepository $popularityEnrichedOfferRepository;

    protected function setUp(): void
    {
        $this->popularityRepository = new InMemoryPopularityRepository();
        $this->decoratedRepository = new InMemoryDocumentRepository();

        $this->popularityEnrichedOfferRepository = new PopularityEnrichedOfferRepository(
            $this->popularityRepository,
            $this->decoratedRepository
        );
    }

    /**
     * @test
     */
    public function it_does_not_add_popularity_score_if_include_metadata_is_false(): void
    {
        $offerId = '4ff559bd-9543-4ae2-900f-fe6d32fd019b';

        $popularity = new Popularity(1234567);
        $this->popularityRepository->saveScore($offerId, $popularity);

        $jsonLd = new JsonDocument($offerId, Json::encode(['@type' => 'Event']));
        $this->decoratedRepository->save($jsonLd);

        $fetchJsonLd = $this->popularityEnrichedOfferRepository->fetch($offerId, false);

        $this->assertEquals($jsonLd, $fetchJsonLd);
    }

    /**
     * @test
     */
    public function it_does_not_attempt_to_add_popularity_score_if_no_offer_json_was_found_when_getting(): void
    {
        $offerId = '4ff559bd-9543-4ae2-900f-fe6d32fd019b';

        $popularity = new Popularity(1234567);
        $this->popularityRepository->saveScore($offerId, $popularity);

        $this->expectException(DocumentDoesNotExist::class);
        $this->popularityEnrichedOfferRepository->fetch($offerId, true);
    }

    /**
     * @test
     */
    public function it_adds_popularity_score_if_include_metadata_is_true_and_offer_json_is_found(): void
    {
        $offerId = '4ff559bd-9543-4ae2-900f-fe6d32fd019b';

        $popularity = new Popularity(1234567);
        $this->popularityRepository->saveScore($offerId, $popularity);

        $jsonLd = new JsonDocument($offerId, Json::encode(['@type' => 'Event']));
        $this->decoratedRepository->save($jsonLd);

        $fetchJsonLd = $this->popularityEnrichedOfferRepository->fetch($offerId, true);

        $expectedJsonLd = new JsonDocument(
            $offerId,
            Json::encode(
                [
                    '@type' => 'Event',
                    'metadata' => [
                        'popularity' => 1234567,
                    ],
                ]
            )
        );

        $this->assertEquals($expectedJsonLd, $fetchJsonLd);
    }
}
