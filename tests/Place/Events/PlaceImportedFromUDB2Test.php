<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\EventSourcing\MainLanguageDefined;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\TestCase;

final class PlaceImportedFromUDB2Test extends TestCase
{
    /**
     * @test
     */
    public function it_implements_main_language_defined(): void
    {
        $event = new PlaceImportedFromUDB2(
            '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
            SampleFiles::read(__DIR__ . '/../actor.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertInstanceOf(MainLanguageDefined::class, $event);
        $this->assertEquals(new Language('nl'), $event->getMainLanguage());
    }

    /**
     * @test
     */
    public function it_can_be_converted_to_modern_granular_events(): void
    {
        $placeImportedFromUDB2 = new PlaceImportedFromUDB2(
            '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
            SampleFiles::read(__DIR__ . '/../actor.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated('0452b4ae-7c18-4b33-a6c6-eba2288c9ac3', 'CC Palethe'),
                new AddressUpdated(
                    '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                    new Address(
                        new Street('Jeugdlaan 2'),
                        new PostalCode('3900'),
                        new Locality('Overpelt'),
                        new CountryCode('BE')
                    )
                ),
            ],
            $placeImportedFromUDB2->toGranularEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_translated_places_to_modern_granular_events(): void
    {
        $placeId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $placeImportedFromUDB2 = new PlaceImportedFromUDB2(
            $placeId,
            SampleFiles::read(__DIR__ . '/../actor_with_translations.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($placeId, 'CC Palethe'),
                new TitleTranslated($placeId, new LegacyLanguage('fr'), 'Centre culturel Palethe'),
                new TitleTranslated($placeId, new LegacyLanguage('de'), 'Kulturzentrum Palethe'),
                new AddressUpdated(
                    '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                    new Address(
                        new Street('Jeugdlaan 2'),
                        new PostalCode('3900'),
                        new Locality('Overpelt'),
                        new CountryCode('BE')
                    )
                ),
            ],
            $placeImportedFromUDB2->toGranularEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_places_without_a_street_to_granular_events(): void
    {
        $placeId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $placeImportedFromUDB2 = new PlaceImportedFromUDB2(
            $placeId,
            SampleFiles::read(__DIR__ . '/../actor_without_street.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($placeId, 'CC Palethe'),
                new AddressUpdated(
                    '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                    new Address(
                        new Street(''),
                        new PostalCode('3900'),
                        new Locality('Overpelt'),
                        new CountryCode('BE')
                    )
                ),
            ],
            $placeImportedFromUDB2->toGranularEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_places_without_a_housenr_to_granular_events(): void
    {
        $placeId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $placeImportedFromUDB2 = new PlaceImportedFromUDB2(
            $placeId,
            SampleFiles::read(__DIR__ . '/../actor_without_housnr.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($placeId, 'CC Palethe'),
                new AddressUpdated(
                    '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                    new Address(
                        new Street('Jeugdlaan'),
                        new PostalCode('3900'),
                        new Locality('Overpelt'),
                        new CountryCode('BE')
                    )
                ),
            ],
            $placeImportedFromUDB2->toGranularEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_places_without_an_address_to_granular_events(): void
    {
        $placeId = 'f6dfcd9d-e43a-4e94-a87e-70253ee77689';
        $placeImportedFromUDB2 = new PlaceImportedFromUDB2(
            $placeId,
            SampleFiles::read(__DIR__ . '/../actor_without_address.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($placeId, 'cultuurcentrum Tessenderlo/Vismarkt'),
            ],
            $placeImportedFromUDB2->toGranularEvents()
        );
    }

    /**
     * @test
     * @dataProvider variousCdbxmlFormatsDataProvider
     */
    public function it_can_convert_various_cdbxml_formats(
        string $placeId,
        array $expected,
        PlaceImportedFromUDB2 $placeImportedFromUDB2
    ): void {
        $this->assertEquals(
            $expected,
            $placeImportedFromUDB2->toGranularEvents()
        );
    }

    public function variousCdbxmlFormatsDataProvider(): array
    {
        return [
            '3.3' => [
                '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                [
                    new TitleUpdated('0452b4ae-7c18-4b33-a6c6-eba2288c9ac3', 'Bogardenkapel'),
                    new AddressUpdated(
                        '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                        new Address(
                            new Street('Katelijnestraat 86'),
                            new PostalCode('8000'),
                            new Locality('Brugge'),
                            new CountryCode('BE')
                        )
                    ),
                ],
                new PlaceImportedFromUDB2(
                    '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3',
                    SampleFiles::read(__DIR__ . '/../actor3.3.xml'),
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
                ),
            ],
            'root_node' => [
                '782c9792-6067-438d-a246-064bb448f086',
                [
                    new TitleUpdated('782c9792-6067-438d-a246-064bb448f086', 'Bogardenkapel'),
                    new AddressUpdated(
                        '782c9792-6067-438d-a246-064bb448f086',
                        new Address(
                            new Street('Katelijnestraat 86'),
                            new PostalCode('8000'),
                            new Locality('Brugge'),
                            new CountryCode('BE')
                        )
                    ),
                ],
                new PlaceImportedFromUDB2(
                    '782c9792-6067-438d-a246-064bb448f086',
                    SampleFiles::read(__DIR__ . '/../actor_with_root_node.xml'),
                    'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL'
                ),
            ],
        ];
    }
}
