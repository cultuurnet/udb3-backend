<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\TestCase;

final class PlaceUpdatedFromUDB2Test extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_converted_to_modern_granular_events(): void
    {
        $placeUpdatedFromUDB2 = new PlaceUpdatedFromUDB2(
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
            $placeUpdatedFromUDB2->toGranularEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_translated_places_to_modern_granular_events(): void
    {
        $placeId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $placeUpdatedFromUDB2 = new PlaceUpdatedFromUDB2(
            $placeId,
            SampleFiles::read(__DIR__ . '/../actor_with_translations.xml'),
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $this->assertEquals(
            [
                new TitleUpdated($placeId, 'CC Palethe'),
                new TitleTranslated($placeId, new Language('fr'), 'Centre culturel Palethe'),
                new TitleTranslated($placeId, new Language('de'), 'Kulturzentrum Palethe'),
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
            $placeUpdatedFromUDB2->toGranularEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_places_without_a_street_to_granular_events(): void
    {
        $placeId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $placeUpdatedFromUDB2 = new PlaceUpdatedFromUDB2(
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
            $placeUpdatedFromUDB2->toGranularEvents()
        );
    }

    /**
     * @test
     */
    public function it_can_convert_places_without_a_housenr_to_granular_events(): void
    {
        $placeId = '0452b4ae-7c18-4b33-a6c6-eba2288c9ac3';
        $placeUpdatedFromUDB2 = new PlaceUpdatedFromUDB2(
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
            $placeUpdatedFromUDB2->toGranularEvents()
        );
    }
}
