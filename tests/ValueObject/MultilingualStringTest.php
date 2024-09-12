<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\ValueObject;

use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Geography\TranslatedAddress;
use CultuurNet\UDB3\Model\ValueObject\Price\TariffName;
use CultuurNet\UDB3\Model\ValueObject\Price\TranslatedTariffName;
use PHPUnit\Framework\TestCase;

class MultilingualStringTest extends TestCase
{
    private Language $originalLanguage;

    private string $originalString;

    /**
     * @var string[]
     */
    private array $translations;

    private MultilingualString $multilingualString;

    public function setUp(): void
    {
        $this->originalLanguage = new Language('nl');
        $this->originalString = 'Hebban olla uogala nestas hagunnan hinase hic anda thu uuat unbidan uue nu';

        $this->translations = [
            'fr' => 'Tous les oiseaux ont commencé nids, sauf moi et vous. Ce que nous attendons?',
            'en' => 'All birds have begun nests, except me and you. What we are waiting for?',
        ];

        $this->multilingualString = (new MultilingualString($this->originalLanguage, $this->originalString))
            ->withTranslation(new Language('fr'), $this->translations['fr'])
            ->withTranslation(new Language('en'), $this->translations['en']);
    }

    /**
     * @test
     */
    public function it_returns_the_original_language_and_string(): void
    {
        $this->assertEquals($this->originalLanguage, $this->multilingualString->getOriginalLanguage());
        $this->assertEquals($this->originalString, $this->multilingualString->getOriginalString());
    }

    /**
     * @test
     */
    public function it_returns_all_translations(): void
    {
        $this->assertEquals($this->translations, $this->multilingualString->getTranslations());
    }

    /**
     * @test
     */
    public function it_returns_all_translations_including_the_original_language__string(): void
    {
        $expected = [
            'nl' =>  'Hebban olla uogala nestas hagunnan hinase hic anda thu uuat unbidan uue nu',
            'fr' => 'Tous les oiseaux ont commencé nids, sauf moi et vous. Ce que nous attendons?',
            'en' => 'All birds have begun nests, except me and you. What we are waiting for?',
        ];

        $this->assertEquals($expected, $this->multilingualString->getTranslationsIncludingOriginal());
    }

    /**
     * @test
     */
    public function it_does_not_allow_translations_of_the_original_language(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Can not translate to original language.');

        $this->multilingualString->withTranslation(
            new Language('nl'),
            'Alle vogels zijn nesten begonnen, behalve ik en jij. Waar wachten wij nu op?'
        );
    }

    /**
     * @test
     * @dataProvider stringForLanguageDataProvider
     *
     * @param Language[] $fallbackLanguages
     */
    public function it_can_return_the_value_for_a_given_language_or_a_fallback_language(
        Language $preferredLanguage,
        array $fallbackLanguages,
        string $expected = null
    ): void {
        $actual = $this->multilingualString->getStringForLanguage($preferredLanguage, ...$fallbackLanguages);
        $this->assertEquals($expected, $actual);
    }

    public function stringForLanguageDataProvider(): array
    {
        return [
            [
                new Language('nl'),
                [new Language('fr'), new Language('en')],
                'Hebban olla uogala nestas hagunnan hinase hic anda thu uuat unbidan uue nu',
            ],
            [
                new Language('de'),
                [new Language('fr'), new Language('en')],
                'Tous les oiseaux ont commencé nids, sauf moi et vous. Ce que nous attendons?',
            ],
            [
                new Language('de'),
                [new Language('es'), new Language('en')],
                'All birds have begun nests, except me and you. What we are waiting for?',
            ],
            [
                new Language('de'),
                [new Language('es'), new Language('ch')],
                null,
            ],
        ];
    }

    /**
     * @test
     */
    public function it_should_be_serializable_and_deserializable(): void
    {
        $expected = [
            'nl' => 'Hebban olla uogala nestas hagunnan hinase hic anda thu uuat unbidan uue nu',
            'fr' => 'Tous les oiseaux ont commencé nids, sauf moi et vous. Ce que nous attendons?',
            'en' => 'All birds have begun nests, except me and you. What we are waiting for?',
        ];

        $actual = $this->multilingualString->serialize();

        $deserialized = MultilingualString::deserialize($actual);

        $this->assertEquals($expected, $actual);
        $this->assertEquals($this->multilingualString, $deserialized);
    }

    /**
     * @test
     */
    public function it_should_be_createable_from_an_udb3_model_translated_value_object(): void
    {
        $given = new TranslatedTariffName(
            new \CultuurNet\UDB3\Model\ValueObject\Translation\Language('nl'),
            new TariffName('Hebban olla uogala nestas hagunnan hinase hic anda thu uuat unbidan uue nu')
        );

        $given = $given
            ->withTranslation(
                new \CultuurNet\UDB3\Model\ValueObject\Translation\Language('fr'),
                new TariffName('Tous les oiseaux ont commencé nids, sauf moi et vous. Ce que nous attendons?')
            )
            ->withTranslation(
                new \CultuurNet\UDB3\Model\ValueObject\Translation\Language('en'),
                new TariffName('All birds have begun nests, except me and you. What we are waiting for?')
            );

        $expected = $this->multilingualString;
        $actual = MultilingualString::fromUdb3ModelTranslatedValueObject($given);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_creating_from_an_unsupported_udb3_model(): void
    {
        $given = new TranslatedAddress(
            new \CultuurNet\UDB3\Model\ValueObject\Translation\Language('nl'),
            new Address(
                new Street('Henegouwsekaai'),
                new PostalCode('1080'),
                new Locality('Brussel'),
                new CountryCode('BE')
            )
        );

        $this->expectException(\InvalidArgumentException::class);

        MultilingualString::fromUdb3ModelTranslatedValueObject($given);
    }
}
