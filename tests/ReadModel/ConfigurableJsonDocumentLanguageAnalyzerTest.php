<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ReadModel;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

class ConfigurableJsonDocumentLanguageAnalyzerTest extends TestCase
{
    private ConfigurableJsonDocumentLanguageAnalyzer $analyzer;

    public function setUp(): void
    {
        $this->analyzer = new ConfigurableJsonDocumentLanguageAnalyzer(
            [
                'name',
                'teaser',
                'body',
            ]
        );
    }

    /**
     * @test
     */
    public function it_should_return_a_list_of_all_languages_found_on_multilingual_fields(): void
    {
        $data = [
            'id' => '919c7904-ecfa-440c-92d0-ae912213c615',
            'name' => [
                'nl' => 'Naam NL',
                'fr' => 'Nom FR',
                'en' => 'Name EN',
                'de' => 'Name DE',
            ],
            'teaser' => [
                'nl' => 'Teaser NL',
                'fr' => 'Teaser FR',
                'de' => 'Teaser DE',
            ],
            'body' => [
                'nl' => 'Teaser NL',
                'en' => 'Teaser EN',
                'fr' => 'Teaser FR',
            ],
        ];

        $document = new JsonDocument('919c7904-ecfa-440c-92d0-ae912213c615', Json::encode($data));

        $expected = [
            new Language('nl'),
            new Language('fr'),
            new Language('en'),
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
            'id' => '919c7904-ecfa-440c-92d0-ae912213c615',
            'name' => [
                'nl' => 'Naam NL',
                'fr' => 'Nom FR',
                'en' => 'Name EN',
                'de' => 'Name DE',
            ],
            'teaser' => [
                'nl' => 'Teaser NL',
                'fr' => 'Teaser FR',
                'de' => 'Teaser DE',
            ],
            'body' => [
                'nl' => 'Teaser NL',
                'en' => 'Teaser EN',
                'fr' => 'Teaser FR',
            ],
        ];

        $document = new JsonDocument('919c7904-ecfa-440c-92d0-ae912213c615', Json::encode($data));

        $expected = [
            new Language('nl'),
            new Language('fr'),
        ];

        $actual = $this->analyzer->determineCompletedLanguages($document);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_ignore_missing_properties(): void
    {
        $data = [
            'id' => '919c7904-ecfa-440c-92d0-ae912213c615',
            'name' => [
                'nl' => 'Naam NL',
                'fr' => 'Nom FR',
                'en' => 'Name EN',
                'de' => 'Name DE',
            ],
            'teaser' => [
                'nl' => 'Teaser NL',
                'fr' => 'Teaser FR',
                'de' => 'Teaser DE',
            ],
        ];

        $document = new JsonDocument('919c7904-ecfa-440c-92d0-ae912213c615', Json::encode($data));

        $expectedAll = [
            new Language('nl'),
            new Language('fr'),
            new Language('en'),
            new Language('de'),
        ];

        $expectedCompleted = [
            new Language('nl'),
            new Language('fr'),
            new Language('de'),
        ];

        $actualAll = $this->analyzer->determineAvailableLanguages($document);
        $actualCompleted = $this->analyzer->determineCompletedLanguages($document);

        $this->assertEquals($expectedAll, $actualAll);
        $this->assertEquals($expectedCompleted, $actualCompleted);
    }
}
