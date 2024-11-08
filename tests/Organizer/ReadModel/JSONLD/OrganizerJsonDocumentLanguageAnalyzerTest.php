<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\JSONLD;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;

class OrganizerJsonDocumentLanguageAnalyzerTest extends TestCase
{
    private OrganizerJsonDocumentLanguageAnalyzer $analyzer;

    public function setUp(): void
    {
        $this->analyzer = new OrganizerJsonDocumentLanguageAnalyzer();
    }

    /**
     * @test
     */
    public function it_should_return_a_list_of_all_languages_found_on_multilingual_fields(): void
    {
        $data = [
            '@id' => 'https://io.uitdatabank.be/organizers/919c7904-ecfa-440c-92d0-ae912213c615',
            'name' => [
                'nl' => 'Naam NL',
            ],
            'address' => [
                'fr' => [
                    'streetAddress' => 'Rue de la loi 1',
                    'postalCode' => '1000',
                    'addressLocality' => 'Bruxelles',
                    'addressCountry' => 'BE',
                ],
            ],
            'description' => [
                'de' => 'Description DE',
            ],
        ];

        $document = new JsonDocument('919c7904-ecfa-440c-92d0-ae912213c615', Json::encode($data));

        $expected = [
            new Language('nl'),
            new Language('fr'),
            new Language('de'),
        ];

        $actual = $this->analyzer->determineAvailableLanguages($document);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_return_a_list_of_languages_found_on_every_one_multilingual_field(): void
    {
        $data = [
            '@id' => 'https://io.uitdatabank.be/events/919c7904-ecfa-440c-92d0-ae912213c615',
            'name' => [
                'nl' => 'Naam NL',
                'fr' => 'Nom FR',
                'en' => 'Name EN',
                'de' => 'Name DE',
            ],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Wetstraat 1',
                    'postalCode' => '1000',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
                'fr' => [
                    'streetAddress' => 'Rue de la loi 1',
                    'postalCode' => '1000',
                    'addressLocality' => 'Bruxelles',
                    'addressCountry' => 'BE',
                ],
            ],
            'description' => [
                'fr' => 'Description FR',
            ],
        ];

        $document = new JsonDocument('919c7904-ecfa-440c-92d0-ae912213c615', Json::encode($data));

        $expected = [
            new Language('fr'),
        ];

        $actual = $this->analyzer->determineCompletedLanguages($document);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_polyfill_name_projections_from_strings_to_multilingual_projections(): void
    {
        $data = [
            '@id' => 'https://io.uitdatabank.be/events/919c7904-ecfa-440c-92d0-ae912213c615',
            'name' => 'Naam NL',
        ];

        $document = new JsonDocument('919c7904-ecfa-440c-92d0-ae912213c615', Json::encode($data));

        $expected = [
            new Language('nl'),
        ];

        $actualAll = $this->analyzer->determineAvailableLanguages($document);
        $actualCompleted = $this->analyzer->determineCompletedLanguages($document);

        $this->assertEquals($expected, $actualAll);
        $this->assertEquals($expected, $actualCompleted);
    }

    /**
     * @test
     */
    public function it_should_polyfill_address_projections_from_a_single_object_to_multilingual_projections(): void
    {
        $data = [
            '@id' => 'https://io.uitdatabank.be/organizers/919c7904-ecfa-440c-92d0-ae912213c615',
            'name' => [
                'nl' => 'Naam NL',
                'fr' => 'Nom FR',
            ],
            'address' => [
                'streetAddress' => 'Wetstraat 1',
                'postalCode' => '1000',
                'addressLocality' => 'Brussel',
                'addressCountry' => 'BE',
            ],
        ];

        $document = new JsonDocument('919c7904-ecfa-440c-92d0-ae912213c615', Json::encode($data));

        $expectedAll = [
            new Language('nl'),
            new Language('fr'),
        ];

        $expectedCompleted = [
            new Language('nl'),
        ];

        $actualAll = $this->analyzer->determineAvailableLanguages($document);
        $actualCompleted = $this->analyzer->determineCompletedLanguages($document);

        $this->assertEquals($expectedAll, $actualAll);
        $this->assertEquals($expectedCompleted, $actualCompleted);
    }
}
