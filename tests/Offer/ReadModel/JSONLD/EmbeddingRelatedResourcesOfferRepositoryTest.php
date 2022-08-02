<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class EmbeddingRelatedResourcesOfferRepositoryTest extends TestCase
{
    private InMemoryDocumentRepository $placeRepository;
    private InMemoryDocumentRepository $organizerRepository;
    private EmbeddingRelatedResourcesOfferRepository $embeddingRelatedResourcesOfferRepository;

    protected function setUp(): void
    {
        $this->placeRepository = new InMemoryDocumentRepository();
        $this->organizerRepository = new InMemoryDocumentRepository();

        $this->embeddingRelatedResourcesOfferRepository = EmbeddingRelatedResourcesOfferRepository::createForEventRepository(
            new InMemoryDocumentRepository(),
            $this->placeRepository,
            $this->organizerRepository
        );
    }

    /**
     * @test
     */
    public function it_does_not_change_documents_without_location_or_organizer_properties(): void
    {
        $original = new JsonDocument('c93a4b0c-0584-4754-93a3-8f14eaef967d', Json::encode(['foo' => 'bar']));
        $this->embeddingRelatedResourcesOfferRepository->save($original);

        $fetched = $this->embeddingRelatedResourcesOfferRepository->fetch('c93a4b0c-0584-4754-93a3-8f14eaef967d');
        $this->assertEquals($original, $fetched);
    }

    /**
     * @test
     */
    public function it_does_not_change_documents_without_id_url_in_location_or_organizer(): void
    {
        $original = new JsonDocument(
            'c93a4b0c-0584-4754-93a3-8f14eaef967d',
            Json::encode(
                [
                    'location' => ['address' => 'foo'],
                    'organizer' => 'bar',
                ]
            )
        );

        $this->embeddingRelatedResourcesOfferRepository->save($original);

        $fetched = $this->embeddingRelatedResourcesOfferRepository->fetch('c93a4b0c-0584-4754-93a3-8f14eaef967d');
        $this->assertEquals($original, $fetched);
    }

    /**
     * @test
     */
    public function it_does_not_embed_related_resources_that_are_not_found(): void
    {
        $original = new JsonDocument(
            'c93a4b0c-0584-4754-93a3-8f14eaef967d',
            Json::encode(
                [
                    'location' => ['@id' => 'https://io.uitdatabank.dev/place/25c668e9-2ad5-4fa0-b923-e3a9ffa6fac5'],
                    'organizer' => ['@id' => 'https://io.uitdatabank.dev/organizer/55f96ba4-840a-4808-bd07-dc0b857fffbb'],
                ]
            )
        );

        $this->embeddingRelatedResourcesOfferRepository->save($original);

        $fetched = $this->embeddingRelatedResourcesOfferRepository->fetch('c93a4b0c-0584-4754-93a3-8f14eaef967d');
        $this->assertEquals($original, $fetched);
    }

    /**
     * @test
     */
    public function it_embeds_related_documents_if_found_and_removes_any_properties_that_were_previously_embedded(): void
    {
        $place = new JsonDocument(
            '25c668e9-2ad5-4fa0-b923-e3a9ffa6fac5',
            Json::encode(
                [
                    '@id' => 'https://io.uitdatabank.dev/place/25c668e9-2ad5-4fa0-b923-e3a9ffa6fac5',
                    'embeddedProperty' => 'should be added',
                ],
            )
        );

        $organizer = new JsonDocument(
            '55f96ba4-840a-4808-bd07-dc0b857fffbb',
            Json::encode(
                [
                    '@id' => 'https://io.uitdatabank.dev/organizer/55f96ba4-840a-4808-bd07-dc0b857fffbb',
                    'embeddedProperty' => 'should be added',
                ],
            )
        );

        $original = new JsonDocument(
            'c93a4b0c-0584-4754-93a3-8f14eaef967d',
            Json::encode(
                [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Example event'],
                    'location' => [
                        '@id' => 'https://io.uitdatabank.dev/place/25c668e9-2ad5-4fa0-b923-e3a9ffa6fac5',
                        'extraProperty' => 'should be removed',
                    ],
                    'organizer' => [
                        '@id' => 'https://io.uitdatabank.dev/organizer/55f96ba4-840a-4808-bd07-dc0b857fffbb',
                        'extraProperty' => 'should be removed',
                    ],
                ]
            )
        );

        $expected = new JsonDocument(
            'c93a4b0c-0584-4754-93a3-8f14eaef967d',
            Json::encode(
                [
                    'mainLanguage' => 'nl',
                    'name' => ['nl' => 'Example event'],
                    'location' => [
                        '@id' => 'https://io.uitdatabank.dev/place/25c668e9-2ad5-4fa0-b923-e3a9ffa6fac5',
                        'embeddedProperty' => 'should be added',
                    ],
                    'organizer' => [
                        '@id' => 'https://io.uitdatabank.dev/organizer/55f96ba4-840a-4808-bd07-dc0b857fffbb',
                        'embeddedProperty' => 'should be added',
                    ],
                ]
            )
        );

        $this->embeddingRelatedResourcesOfferRepository->save($original);
        $this->placeRepository->save($place);
        $this->organizerRepository->save($organizer);

        $fetched = $this->embeddingRelatedResourcesOfferRepository->fetch('c93a4b0c-0584-4754-93a3-8f14eaef967d');
        $this->assertEquals($expected, $fetched);
    }
}
