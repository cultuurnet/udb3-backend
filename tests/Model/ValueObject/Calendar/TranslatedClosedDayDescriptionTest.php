<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;

final class TranslatedClosedDayDescriptionTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_a_translated_closed_day_description_with_single_language(): void
    {
        $description = new TranslatedClosedDayDescription(
            new Language('nl'),
            new AdjustedDescription('Gesloten op eerste kerstdag')
        );

        $nlTranslation = $description->getTranslation(new Language('nl'));
        $this->assertEquals('Gesloten op eerste kerstdag', $nlTranslation->toString());
    }

    /**
     * @test
     */
    public function it_creates_a_translated_closed_day_description_with_multiple_languages(): void
    {
        $description = new TranslatedClosedDayDescription(
            new Language('nl'),
            new AdjustedDescription('Gesloten op eerste kerstdag')
        );

        $description = $description->withTranslation(
            new Language('fr'),
            new AdjustedDescription('Fermé pour Noël')
        );

        $description = $description->withTranslation(
            new Language('en'),
            new AdjustedDescription('Closed for Christmas')
        );

        $nlTranslation = $description->getTranslation(new Language('nl'));
        $this->assertEquals('Gesloten op eerste kerstdag', $nlTranslation->toString());

        $frTranslation = $description->getTranslation(new Language('fr'));
        $this->assertEquals('Fermé pour Noël', $frTranslation->toString());

        $enTranslation = $description->getTranslation(new Language('en'));
        $this->assertEquals('Closed for Christmas', $enTranslation->toString());
    }

    /**
     * @test
     */
    public function it_returns_all_languages(): void
    {
        $description = new TranslatedClosedDayDescription(
            new Language('nl'),
            new AdjustedDescription('Gesloten op eerste kerstdag')
        );

        $description = $description->withTranslation(
            new Language('fr'),
            new AdjustedDescription('Fermé pour Noël')
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
        $description = new TranslatedClosedDayDescription(
            new Language('nl'),
            new AdjustedDescription('Eerste versie')
        );

        $description = $description->withTranslation(
            new Language('nl'),
            new AdjustedDescription('Tweede versie')
        );

        $nlTranslation = $description->getTranslation(new Language('nl'));
        $this->assertEquals('Tweede versie', $nlTranslation->toString());

        // Should still have only one language
        $languages = $description->getLanguages();
        $this->assertCount(1, $languages);
    }

    /**
     * @test
     */
    public function it_gets_original_language(): void
    {
        $originalLanguage = new Language('nl');
        $description = new TranslatedClosedDayDescription(
            $originalLanguage,
            new AdjustedDescription('Gesloten op eerste kerstdag')
        );

        $this->assertEquals($originalLanguage, $description->getOriginalLanguage());
    }
}
