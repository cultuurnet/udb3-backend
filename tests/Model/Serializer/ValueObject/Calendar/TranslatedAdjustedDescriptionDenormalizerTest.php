<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\UnsupportedException;

final class TranslatedAdjustedDescriptionDenormalizerTest extends TestCase
{
    private TranslatedAdjustedDescriptionDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new TranslatedAdjustedDescriptionDenormalizer();
    }

    /**
     * @test
     */
    public function it_denormalizes_a_single_language_description(): void
    {
        $data = ['nl' => 'Kerstfeest gesloten'];

        $result = $this->denormalizer->denormalize($data, TranslatedAdjustedDescription::class);

        $this->assertInstanceOf(TranslatedAdjustedDescription::class, $result);
        $this->assertTrue($result->getOriginalLanguage()->sameAs(new Language('nl')));

        $nlDescription = $result->getTranslation(new Language('nl'));
        $this->assertInstanceOf(AdjustedDescription::class, $nlDescription);
        $this->assertSame('Kerstfeest gesloten', $nlDescription->toString());
    }

    /**
     * @test
     */
    public function it_denormalizes_multiple_language_descriptions(): void
    {
        $data = [
            'nl' => 'Kerstfeest gesloten',
            'fr' => 'Fermé pour Noël',
            'en' => 'Closed for Christmas',
        ];

        $result = $this->denormalizer->denormalize($data, TranslatedAdjustedDescription::class);

        $this->assertInstanceOf(TranslatedAdjustedDescription::class, $result);
        $this->assertSame('Kerstfeest gesloten', $result->getTranslation(new Language('nl'))->toString());
        $this->assertSame('Fermé pour Noël', $result->getTranslation(new Language('fr'))->toString());
        $this->assertSame('Closed for Christmas', $result->getTranslation(new Language('en'))->toString());
    }

    /**
     * @test
     */
    public function it_uses_the_first_key_as_original_language_when_no_context_provided(): void
    {
        $data = [
            'fr' => 'Fermé pour Noël',
            'nl' => 'Kerstfeest gesloten',
        ];

        $result = $this->denormalizer->denormalize($data, TranslatedAdjustedDescription::class);

        $this->assertTrue($result->getOriginalLanguage()->sameAs(new Language('fr')));
    }

    /**
     * @test
     */
    public function it_respects_context_original_language(): void
    {
        $data = [
            'nl' => 'Kerstfeest gesloten',
            'fr' => 'Fermé pour Noël',
        ];

        $result = $this->denormalizer->denormalize(
            $data,
            TranslatedAdjustedDescription::class,
            null,
            ['originalLanguage' => 'nl']
        );

        $this->assertTrue($result->getOriginalLanguage()->sameAs(new Language('nl')));
    }

    /** @test */
    public function it_skips_invalid_language_codes(): void
    {
        $data = [
            'nl' => 'Kerstfeest gesloten',
            'invalid-code' => 'Invalid',
            'fr' => 'Fermé pour Noël',
        ];

        $result = $this->denormalizer->denormalize($data, TranslatedAdjustedDescription::class);

        $this->assertSame('Kerstfeest gesloten', $result->getTranslation(new Language('nl'))->toString());
        $this->assertSame('Fermé pour Noël', $result->getTranslation(new Language('fr'))->toString());
        $this->assertCount(2, iterator_to_array($result->getLanguages()));
    }

    /** @test */
    public function it_throws_when_accessing_a_skipped_language(): void
    {
        $data = [
            'nl' => 'Kerstfeest gesloten',
            'invalid-code' => 'Invalid',
            'fr' => 'Fermé pour Noël',
        ];

        $result = $this->denormalizer->denormalize($data, TranslatedAdjustedDescription::class);

        $this->expectException(\OutOfBoundsException::class);
        $this->expectExceptionMessage('No translation found');
        $result->getTranslation(new Language('de'));
    }

    /**
     * @test
     */
    public function it_throws_for_empty_array(): void
    {
        $this->expectException(UnsupportedException::class);
        $this->expectExceptionMessage('at least one value');

        $this->denormalizer->denormalize([], TranslatedAdjustedDescription::class);
    }

    /**
     * @test
     */
    public function it_supports_denormalization(): void
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization(
            ['nl' => 'Test'],
            TranslatedAdjustedDescription::class
        ));

        $this->assertFalse($this->denormalizer->supportsDenormalization(
            ['nl' => 'Test'],
            'SomeOtherClass'
        ));
    }
}
