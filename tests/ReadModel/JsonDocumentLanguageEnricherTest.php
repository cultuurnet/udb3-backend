<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Json;
use PHPUnit\Framework\TestCase;

class JsonDocumentLanguageEnricherTest extends TestCase
{
    private JsonDocumentLanguageEnricher $enricher;

    public function setUp(): void
    {
        $languageAnalyzer = new ConfigurableJsonDocumentLanguageAnalyzer(
            [
                'name',
                'description',
            ]
        );

        $this->enricher = new JsonDocumentLanguageEnricher($languageAnalyzer);
    }

    /**
     * @test
     */
    public function it_should_enrich_a_json_document_with_a_list_of_all_languages_and_a_list_of_completed_languages(): void
    {
        $givenJsonDocument = new JsonDocument(
            '41278834-8a90-4b4a-bca2-c3189787146d',
            Json::encode(
                [
                    'name' => [
                        'nl' => 'Naam NL',
                        'fr' => 'Nom FR',
                        'en' => 'Name EN',
                    ],
                    'description' => [
                        'nl' => 'Beschrijving NL',
                        'en' => 'Description EN',
                    ],
                ]
            )
        );

        $expectedJsonDocument = new JsonDocument(
            '41278834-8a90-4b4a-bca2-c3189787146d',
            Json::encode(
                [
                    'name' => [
                        'nl' => 'Naam NL',
                        'fr' => 'Nom FR',
                        'en' => 'Name EN',
                    ],
                    'description' => [
                        'nl' => 'Beschrijving NL',
                        'en' => 'Description EN',
                    ],
                    'languages' => [
                        'nl',
                        'fr',
                        'en',
                    ],
                    'completedLanguages' => [
                        'nl',
                        'en',
                    ],
                ]
            )
        );

        $actualDocument = $this->enricher->enrich($givenJsonDocument, new Metadata());

        $this->assertEquals($expectedJsonDocument, $actualDocument);
    }

    /**
     * @test
     */
    public function it_should_not_enrich_empty_properties_if_no_languages_are_found(): void
    {
        $givenJsonDocument = new JsonDocument(
            '41278834-8a90-4b4a-bca2-c3189787146d',
            Json::encode(
                [
                    'foo' => 'bar',
                ]
            )
        );

        $this->assertEquals($givenJsonDocument, $this->enricher->enrich($givenJsonDocument, new Metadata()));
    }
}
