<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Term\TermRepository;
use PHPUnit\Framework\TestCase;

class TermLabelOfferRepositoryDecoratorTest extends TestCase
{
    private const MAPPING = [
        '0.41.0.0.0' => [
            'name' => [
                'nl' => 'Thema of pretpark',
                'fr' => 'Parc à thème ou parc d\'attractions',
                'de' => 'Unterhaltungspark',
                'en' => 'Theme park',
            ],
        ],
        '3CuHvenJ+EGkcvhXLg9Ykg' => [
            'name' => [
                'nl' => 'Archeologische Site',
            ],
        ],
        'no_name' => [],
        'no_name_nl' => [
            'name' => [
                'fr' => '...',
            ],
        ],
    ];

    /**
     * @var TermLabelOfferRepositoryDecorator
     */
    private $termLabelDecorator;

    protected function setUp(): void
    {
        $this->termLabelDecorator = new TermLabelOfferRepositoryDecorator(
            new InMemoryDocumentRepository(),
            new TermRepository(self::MAPPING)
        );
    }

    /**
     * @test
     */
    public function it_should_replace_known_term_labels(): void
    {
        $id = '5624b810-c340-40a4-8f38-0393eca59bfe';

        $givenJson = [
            'terms' => [
                [
                    'id' => '0.41.0.0.0',
                    'label' => 'Should be changed',
                    'domain' => 'mock',
                ],
                [
                    'id' => '3CuHvenJ+EGkcvhXLg9Ykg',
                    'domain' => 'mock',
                ],
                [
                    'id' => 'no_name',
                    'label' => 'Should remain unchanged',
                    'domain' => 'mock',
                ],
                [
                    'id' => 'no_name_nl',
                    'label' => 'Should remain unchanged',
                    'domain' => 'mock',
                ],
                [
                    'id' => 'does_not_exist',
                    'label' => 'Should remain unchanged',
                    'domain' => 'mock',
                ],
            ],
        ];

        $givenDocument = new JsonDocument($id, Json::encode($givenJson));

        $expectedJson = [
            'terms' => [
                [
                    'id' => '0.41.0.0.0',
                    'label' => 'Thema of pretpark',
                    'domain' => 'mock',
                ],
                [
                    'id' => '3CuHvenJ+EGkcvhXLg9Ykg',
                    'label' => 'Archeologische Site',
                    'domain' => 'mock',
                ],
                [
                    'id' => 'no_name',
                    'label' => 'Should remain unchanged',
                    'domain' => 'mock',
                ],
                [
                    'id' => 'no_name_nl',
                    'label' => 'Should remain unchanged',
                    'domain' => 'mock',
                ],
                [
                    'id' => 'does_not_exist',
                    'label' => 'Should remain unchanged',
                    'domain' => 'mock',
                ],
            ],
        ];

        $this->termLabelDecorator->save($givenDocument);
        $actualDocument = $this->termLabelDecorator->fetch($id);
        $actualJson = $actualDocument->getAssocBody();

        $this->assertEquals($expectedJson, $actualJson);
    }

    /**
     * @test
     */
    public function it_should_ignore_offers_without_terms_property(): void
    {
        $id = 'b8fa0fcb-4062-42a7-9e39-d3a515421ec9';

        $givenJson = ['foo' => 'bar'];
        $givenDocument = new JsonDocument($id, Json::encode($givenJson));

        $expectedJson = ['foo' => 'bar'];

        $this->termLabelDecorator->save($givenDocument);
        $actualDocument = $this->termLabelDecorator->fetch($id);
        $actualJson = $actualDocument->getAssocBody();

        $this->assertEquals($expectedJson, $actualJson);
    }

    /**
     * @test
     */
    public function it_should_ignore_offers_with_terms_property_that_is_not_an_array(): void
    {
        $id = 'b220e450-0cec-4607-84a2-0026dfda77ff';

        $givenJson = ['terms' => 'foo'];
        $givenDocument = new JsonDocument($id, Json::encode($givenJson));

        $expectedJson = ['terms' => 'foo'];

        $this->termLabelDecorator->save($givenDocument);
        $actualDocument = $this->termLabelDecorator->fetch($id);
        $actualJson = $actualDocument->getAssocBody();

        $this->assertEquals($expectedJson, $actualJson);
    }
}
