<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Media\MediaUrlMapping;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

final class MediaUrlOfferRepositoryDecoratorTest extends TestCase
{
    private const GIVEN_JSON = [
        'mediaObject' => [
            [
                '@id' => 'https://io.uitdatabank.be/images/da02d848-eb11-4bfa-a566-d8bd3b856990',
                '@type' => 'schema:ImageObject',
                'contentUrl' => 'https://media.uitdatabank.be/20190131/da02d848-eb11-4bfa-a566-d8bd3b856990.jpeg',
                'thumbnailUrl' => 'https://media.uitdatabank.be/20190131/da02d848-eb11-4bfa-a566-d8bd3b856990.jpeg',
                'description' => 'test',
                'copyrightHolder' => 'cc2',
                'inLanguage' => 'nl',
            ],
            [
                '@id' => 'https://io.uitdatabank.be/images/21defff6-59e4-49f4-ab84-83302ff20010',
                '@type' => 'schema:ImageObject',
                'contentUrl' => 'https://io.uitdatabank.be/images/21defff6-59e4-49f4-ab84-83302ff20010.jpeg',
                'thumbnailUrl' => 'https://io.uitdatabank.be/images/21defff6-59e4-49f4-ab84-83302ff20010.jpeg',
                'description' => 'test',
                'copyrightHolder' => 'cc2',
                'inLanguage' => 'nl',
            ],
            [
                '@id' => 'https://io.uitdatabank.be/images/73585fe0-577b-488f-95f4-746940d7cce9',
                '@type' => 'schema:ImageObject',
                'contentUrl' => 'https://io.uitdatabank.be/images/73585fe0-577b-488f-95f4-746940d7cce9.jpeg',
                'thumbnailUrl' => 'https://io.uitdatabank.be/images/73585fe0-577b-488f-95f4-746940d7cce9.jpeg',
                'description' => 'test',
                'copyrightHolder' => 'cc3',
                'inLanguage' => 'nl',
            ],
        ],
        'image' => 'https://io.uitdatabank.be/images/da02d848-eb11-4bfa-a566-d8bd3b856990.jpeg',
        'location' => [
            'mediaObject' => [
                [
                    '@id' => 'https://io.uitdatabank.be/images/34a6f347-a378-4a7a-a017-56ecdf0a099b',
                    '@type' => 'schema:ImageObject',
                    'contentUrl' => 'https://io.uitdatabank.be/images/34a6f347-a378-4a7a-a017-56ecdf0a099b',
                    'thumbnailUrl' => 'https://io.uitdatabank.be/images/34a6f347-a378-4a7a-a017-56ecdf0a099b',
                    'description' => 'test',
                    'copyrightHolder' => 'cc4',
                    'inLanguage' => 'nl',
                ],
            ],
            'image' => 'https://io.uitdatabank.be/images/34a6f347-a378-4a7a-a017-56ecdf0a099b',
        ],
    ];

    private const POLYFILLED_JSON = [
        'mediaObject' => [
            [
                '@id' => 'https://io.uitdatabank.be/images/da02d848-eb11-4bfa-a566-d8bd3b856990',
                '@type' => 'schema:ImageObject',
                'contentUrl' => 'https://images.uitdatabank.be/20190131/da02d848-eb11-4bfa-a566-d8bd3b856990.jpeg',
                'thumbnailUrl' => 'https://images.uitdatabank.be/20190131/da02d848-eb11-4bfa-a566-d8bd3b856990.jpeg',
                'description' => 'test',
                'copyrightHolder' => 'cc2',
                'inLanguage' => 'nl',
            ],
            [
                '@id' => 'https://io.uitdatabank.be/images/21defff6-59e4-49f4-ab84-83302ff20010',
                '@type' => 'schema:ImageObject',
                'contentUrl' => 'https://images.uitdatabank.be/21defff6-59e4-49f4-ab84-83302ff20010.jpeg',
                'thumbnailUrl' => 'https://images.uitdatabank.be/21defff6-59e4-49f4-ab84-83302ff20010.jpeg',
                'description' => 'test',
                'copyrightHolder' => 'cc2',
                'inLanguage' => 'nl',
            ],
            [
                '@id' => 'https://io.uitdatabank.be/images/73585fe0-577b-488f-95f4-746940d7cce9',
                '@type' => 'schema:ImageObject',
                'contentUrl' => 'https://images.uitdatabank.be/73585fe0-577b-488f-95f4-746940d7cce9.jpeg',
                'thumbnailUrl' => 'https://images.uitdatabank.be/73585fe0-577b-488f-95f4-746940d7cce9.jpeg',
                'description' => 'test',
                'copyrightHolder' => 'cc3',
                'inLanguage' => 'nl',
            ],
        ],
        'image' => 'https://images.uitdatabank.be/da02d848-eb11-4bfa-a566-d8bd3b856990.jpeg',
        'location' => [
            'mediaObject' => [
                [
                    '@id' => 'https://io.uitdatabank.be/images/34a6f347-a378-4a7a-a017-56ecdf0a099b',
                    '@type' => 'schema:ImageObject',
                    'contentUrl' => 'https://images.uitdatabank.be/34a6f347-a378-4a7a-a017-56ecdf0a099b',
                    'thumbnailUrl' => 'https://images.uitdatabank.be/34a6f347-a378-4a7a-a017-56ecdf0a099b',
                    'description' => 'test',
                    'copyrightHolder' => 'cc4',
                    'inLanguage' => 'nl',
                ],
            ],
            'image' => 'https://images.uitdatabank.be/34a6f347-a378-4a7a-a017-56ecdf0a099b',
        ],
    ];

    /**
     * @test
     */
    public function it_should_replace_legacy_urls_when_enabled(): void
    {
        $id = '5624b810-c340-40a4-8f38-0393eca59bfe';
        $mapping = [
            'udb2' => [
                'enabled' => true,
                'legacy_url' => 'https://media.uitdatabank.be/',
                'url' => 'https://images.uitdatabank.be/',
            ],
            'udb3' => [
                'enabled' => true,
                'legacy_url' => 'https://io.uitdatabank.be/images/',
                'url' => 'https://images.uitdatabank.be/',
            ],
        ];

        $mediaUrlOfferRepositoryDecoratorDecorator = new MediaUrlOfferRepositoryDecorator(
            new InMemoryDocumentRepository(),
            new MediaUrlMapping($mapping)
        );
        $givenDocument = new JsonDocument($id, Json::encode(self::GIVEN_JSON));

        $mediaUrlOfferRepositoryDecoratorDecorator->save($givenDocument);
        $actualDocument = $mediaUrlOfferRepositoryDecoratorDecorator->fetch($id);
        $actualJson = $actualDocument->getAssocBody();

        $this->assertEquals(self::POLYFILLED_JSON, $actualJson);
    }

    /**
     * @test
     */
    public function it_should_not_replace_legacy_urls_when_not_enabled(): void
    {
        $id = '5624b810-c340-40a4-8f38-0393eca59bfe';
        $mapping = [
            'udb2' => [
                'enabled' => false,
                'legacy_url' => 'https://media.uitdatabank.be/',
                'url' => 'https://images.uitdatabank.be/',
            ],
            'udb3' => [
                'enabled' => false,
                'legacy_url' => 'https://io.uitdatabank.be/images/',
                'url' => 'https://images.uitdatabank.be/',
            ],
        ];

        $mediaUrlOfferRepositoryDecorator = new MediaUrlOfferRepositoryDecorator(
            new InMemoryDocumentRepository(),
            new MediaUrlMapping($mapping)
        );
        $givenDocument = new JsonDocument($id, Json::encode(self::GIVEN_JSON));

        $mediaUrlOfferRepositoryDecorator->save($givenDocument);
        $actualDocument = $mediaUrlOfferRepositoryDecorator->fetch($id);
        $actualJson = $actualDocument->getAssocBody();

        $this->assertEquals(self::GIVEN_JSON, $actualJson);
    }
}
