<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

final class TranslatedAdjustedOpeningHoursDescriptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_translated_description_with_single_language(): void
    {
        $description = new TranslatedAdjustedOpeningHoursDescription(
            new Language('nl'),
            new AdjustedDescription('Kerstvakantie')
        );

        $nlTranslation = $description->getTranslation(new Language('nl'));
        $this->assertEquals('Kerstvakantie', $nlTranslation->toString());
    }

    /**
     * @test
     */
    public function it_creates_a_translated_description_with_multiple_languages(): void
    {
        $description = new TranslatedAdjustedOpeningHoursDescription(
            new Language('nl'),
            new AdjustedDescription('Kerstvakantie')
        );

        $description = $description->withTranslation(
            new Language('fr'),
            new AdjustedDescription('Vacances de Noël')
        );

        $description = $description->withTranslation(
            new Language('en'),
            new AdjustedDescription('Christmas holiday')
        );

        $this->assertEquals('Kerstvakantie', $description->getTranslation(new Language('nl'))->toString());
        $this->assertEquals('Vacances de Noël', $description->getTranslation(new Language('fr'))->toString());
        $this->assertEquals('Christmas holiday', $description->getTranslation(new Language('en'))->toString());
    }

    /**
     * @test
     */
    public function it_returns_all_languages(): void
    {
        $description = new TranslatedAdjustedOpeningHoursDescription(
            new Language('nl'),
            new AdjustedDescription('Kerstvakantie')
        );

        $description = $description->withTranslation(
            new Language('fr'),
            new AdjustedDescription('Vacances de Noël')
        );

        $languages = $description->getLanguages();

        $languagesArray = iterator_to_array($languages);
        $this->assertCount(2, $languagesArray);
        $this->assertTrue(in_array(new Language('nl'), $languagesArray));
        $this->assertTrue(in_array(new Language('fr'), $languagesArray));
    }

    /**
     * @test
     */
    public function it_updates_existing_translation(): void
    {
        $description = new TranslatedAdjustedOpeningHoursDescription(
            new Language('nl'),
            new AdjustedDescription('Eerste versie')
        );

        $description = $description->withTranslation(
            new Language('nl'),
            new AdjustedDescription('Tweede versie')
        );

        $this->assertEquals('Tweede versie', $description->getTranslation(new Language('nl'))->toString());

        // Should still have only one language
        $this->assertCount(1, $description->getLanguages());
    }

    /**
     * @test
     */
    public function it_gets_original_language(): void
    {
        $originalLanguage = new Language('nl');
        $description = new TranslatedAdjustedOpeningHoursDescription(
            $originalLanguage,
            new AdjustedDescription('Kerstvakantie')
        );

        $this->assertEquals($originalLanguage, $description->getOriginalLanguage());
    }
}
