<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Model\Serializer\ValueObject\Calendar;

use CultuurNet\UDB3\Model\ValueObject\Calendar\AdjustedDescription;
use CultuurNet\UDB3\Model\ValueObject\Calendar\TranslatedAdjustedOpeningHoursDescription;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\UnsupportedException;

final class TranslatedAdjustedOpeningHoursDescriptionDenormalizerTest extends TestCase
{
    private TranslatedAdjustedOpeningHoursDescriptionDenormalizer $denormalizer;

    protected function setUp(): void
    {
        $this->denormalizer = new TranslatedAdjustedOpeningHoursDescriptionDenormalizer();
    }

    /**
     * @test
     */
    public function it_denormalizes_a_single_language_description(): void
    {
        $data = ['nl' => 'Kerstvakantie'];

        $result = $this->denormalizer->denormalize($data, TranslatedAdjustedOpeningHoursDescription::class);

        $this->assertInstanceOf(TranslatedAdjustedOpeningHoursDescription::class, $result);
        $this->assertTrue($result->getOriginalLanguage()->sameAs(new Language('nl')));

        $nlDescription = $result->getTranslation(new Language('nl'));
        $this->assertInstanceOf(AdjustedDescription::class, $nlDescription);
        $this->assertSame('Kerstvakantie', $nlDescription->toString());
    }

    /**
     * @test
     */
    public function it_denormalizes_multiple_language_descriptions(): void
    {
        $data = [
            'nl' => 'Kerstvakantie',
            'fr' => 'Vacances de Noël',
            'en' => 'Christmas holiday',
        ];

        $result = $this->denormalizer->denormalize($data, TranslatedAdjustedOpeningHoursDescription::class);

        $this->assertInstanceOf(TranslatedAdjustedOpeningHoursDescription::class, $result);
        $this->assertSame('Kerstvakantie', $result->getTranslation(new Language('nl'))->toString());
        $this->assertSame('Vacances de Noël', $result->getTranslation(new Language('fr'))->toString());
        $this->assertSame('Christmas holiday', $result->getTranslation(new Language('en'))->toString());
    }

    /**
     * @test
     */
    public function it_uses_the_first_key_as_original_language_when_no_context_provided(): void
    {
        $data = [
            'fr' => 'Vacances de Noël',
            'nl' => 'Kerstvakantie',
        ];

        $result = $this->denormalizer->denormalize($data, TranslatedAdjustedOpeningHoursDescription::class);

        // First key should be original language
        $this->assertTrue($result->getOriginalLanguage()->sameAs(new Language('fr')));
    }

    /**
     * @test
     */
    public function it_respects_context_original_language(): void
    {
        $data = [
            'nl' => 'Kerstvakantie',
            'fr' => 'Vacances de Noël',
        ];

        $result = $this->denormalizer->denormalize(
            $data,
            TranslatedAdjustedOpeningHoursDescription::class,
            null,
            ['originalLanguage' => 'nl']
        );

        $this->assertTrue($result->getOriginalLanguage()->sameAs(new Language('nl')));
    }

    /**
     * @test
     */
    public function it_skips_invalid_language_codes(): void
    {
        $data = [
            'nl' => 'Kerstvakantie',
            'invalid-code' => 'Invalid',
            'fr' => 'Vacances de Noël',
        ];

        $result = $this->denormalizer->denormalize($data, TranslatedAdjustedOpeningHoursDescription::class);

        // Should have nl and fr, but not invalid-code
        $this->assertSame('Kerstvakantie', $result->getTranslation(new Language('nl'))->toString());
        $this->assertSame('Vacances de Noël', $result->getTranslation(new Language('fr'))->toString());

        // Invalid code should raise exception when trying to access it
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

        $this->denormalizer->denormalize([], TranslatedAdjustedOpeningHoursDescription::class);
    }

    /**
     * @test
     */
    public function it_supports_denormalization(): void
    {
        $this->assertTrue($this->denormalizer->supportsDenormalization(
            ['nl' => 'Test'],
            TranslatedAdjustedOpeningHoursDescription::class
        ));

        $this->assertFalse($this->denormalizer->supportsDenormalization(
            ['nl' => 'Test'],
            'SomeOtherClass'
        ));
    }
}
