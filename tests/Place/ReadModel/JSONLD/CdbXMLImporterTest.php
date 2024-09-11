<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\JSONLD;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultuurNet\UDB3\Calendar\CalendarFactory;
use CultuurNet\UDB3\Cdb\ActorItemFactory;
use CultuurNet\UDB3\Cdb\CdbXmlPriceInfoParser;
use CultuurNet\UDB3\Cdb\CdbXMLToJsonLDLabelImporter;
use CultuurNet\UDB3\Cdb\PriceDescriptionParser;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXmlContactInfoImporter;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXMLItemBaseImporter;
use CultuurNet\UDB3\SampleFiles;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class CdbXMLImporterTest extends TestCase
{
    protected CdbXMLImporter $importer;

    public function setUp(): void
    {
        $this->importer = new CdbXMLImporter(
            new CdbXMLItemBaseImporter(
                new CdbXmlPriceInfoParser(
                    new PriceDescriptionParser(
                        new NumberFormatRepository(),
                        new CurrencyRepository()
                    )
                ),
                [
                    'nl' => 'Basistarief',
                    'fr' => 'Tarif de base',
                    'en' => 'Base tarif',
                    'de' => 'Basisrate',
                ]
            ),
            new CalendarFactory(),
            new CdbXmlContactInfoImporter(),
            new CdbXMLToJsonLDLabelImporter($this->createMock(ReadRepositoryInterface::class))
        );
        date_default_timezone_set('Europe/Brussels');
    }

    /**
     * @param string $fileName
     * @param string $version
     */
    private function createJsonPlaceFromCdbXml($fileName, $version = '3.2'): \stdClass
    {
        $cdbXml = SampleFiles::read(
            __DIR__ . '/' . $fileName
        );

        $actor = ActorItemFactory::createActorFromCdbXml(
            "http://www.cultuurdatabank.com/XMLSchema/CdbXSD/{$version}/FINAL",
            $cdbXml
        );

        $jsonPlace = $this->importer->documentWithCdbXML(
            new \stdClass(),
            $actor
        );

        return $jsonPlace;
    }

    /**
     * @param string $fileName
     */
    private function createJsonPlaceFromCdbXmlWithWeekScheme($fileName): \stdClass
    {
        $cdbXml = SampleFiles::read(
            __DIR__ . '/Calendar/' . $fileName
        );

        $actor = ActorItemFactory::createActorFromCdbXml(
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL',
            $cdbXml
        );

        $jsonPlace = $this->importer->documentWithCdbXML(
            new \stdClass(),
            $actor
        );

        return $jsonPlace;
    }

    /**
     * @test
     */
    public function it_imports_the_publication_info(): void
    {
        $jsonPlace = $this->createJsonPlaceFromCdbXml('place_with_long_description.cdbxml.xml');

        $this->assertEquals('2013-07-18T09:04:37+02:00', $jsonPlace->modified);
        $this->assertEquals('cultuurnet001', $jsonPlace->creator);
        $this->assertEquals('Invoerders Algemeen ', $jsonPlace->publisher);
        $this->assertEquals('2013-07-18T09:04:07+02:00', $jsonPlace->availableFrom);
        $this->assertEquals(['Cultuurnet:organisation_1565'], $jsonPlace->sameAs);
    }

    /**
     * @test
     */
    public function it_should_copy_over_a_known_workflow_status(): void
    {
        $jsonPlace = $this->createJsonPlaceFromCdbXml('place_with_long_description.cdbxml.xml');

        $this->assertEquals('APPROVED', $jsonPlace->workflowStatus);
    }

    /**
     * @test
     */
    public function it_sets_all_ages_as_default(): void
    {
        $jsonPlace = $this->createJsonPlaceFromCdbXml('place_with_long_description.cdbxml.xml');

        $this->assertEquals('-', $jsonPlace->typicalAgeRange);
    }

    /**
     * @test
     */
    public function it_should_mark_a_place_as_ready_for_validation_when_importing_without_a_workflow_status(): void
    {
        $jsonPlace = $this->createJsonPlaceFromCdbXml('place_with_image.cdbxml.xml');

        $this->assertEquals('READY_FOR_VALIDATION', $jsonPlace->workflowStatus);
    }

    /**
     * @test
     */
    public function it_throws_an_exception_when_the_workflow_status_is_unknown(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createJsonPlaceFromCdbXml('place_with_unknown_workflow_status.cdbxml.xml');
    }

    /**
     * @test
     */
    public function it_handles_place_without_week_scheme(): void
    {
        $jsonPlace = $this->createJsonPlaceFromCdbXmlWithWeekScheme('place_no_week_scheme.xml');
        $this->assertEquals('permanent', $jsonPlace->calendarType);
    }

    /**
     * @test
     */
    public function it_handles_place_with_week_scheme(): void
    {
        $jsonPlace = $this->createJsonPlaceFromCdbXmlWithWeekScheme('place_with_week_scheme.xml');
        $this->assertEquals('permanent', $jsonPlace->calendarType);
        $this->assertEquals(
            [
                [
                    'dayOfWeek' => [
                        'wednesday',
                        'thursday',
                        'friday',
                        'saturday',
                        'sunday',
                    ],
                    'opens' => '14:00',
                    'closes' => '17:00',
                ],
            ],
            $jsonPlace->openingHours
        );
    }

    /**
     * @test
     */
    public function it_handles_place_with_week_scheme_no_hours(): void
    {
        $jsonPlace = $this->createJsonPlaceFromCdbXmlWithWeekScheme('place_with_week_scheme_no_hours.xml');
        $this->assertEquals('permanent', $jsonPlace->calendarType);
        $this->assertEquals(
            [
                [
                    'dayOfWeek' => [
                        'saturday',
                    ],
                    'opens' => '00:00',
                    'closes' => '00:00',
                ],
            ],
            $jsonPlace->openingHours
        );
    }

    /**
     * @test
     */
    public function it_handles_place_with_week_scheme_no_closing_hours(): void
    {
        $jsonPlace = $this->createJsonPlaceFromCdbXmlWithWeekScheme('place_with_week_scheme_no_closing_hours.xml');
        $this->assertEquals('permanent', $jsonPlace->calendarType);
        $this->assertEquals(
            [
                [
                    'dayOfWeek' => [
                        'monday',
                        'tuesday',
                        'wednesday',
                        'thursday',
                        'friday',
                        'saturday',
                        'sunday',
                    ],
                    'opens' => '11:00',
                    'closes' => '11:00',
                ],
            ],
            $jsonPlace->openingHours
        );
    }

    /**
     * @test
     */
    public function it_handles_place_with_week_scheme_missing_closing_hours(): void
    {
        $jsonPlace = $this->createJsonPlaceFromCdbXmlWithWeekScheme('place_with_week_scheme_missing_closing_hours.xml');
        $this->assertEquals('permanent', $jsonPlace->calendarType);
        $this->assertEquals(
            [
                [
                    'dayOfWeek' => [
                        'wednesday',
                        'sunday',
                    ],
                    'opens' => '19:00',
                    'closes' => '23:00',
                ],
                [
                    'dayOfWeek' => [
                        'friday',
                    ],
                    'opens' => '19:00',
                    'closes' => '01:00',
                ],
                [
                    'dayOfWeek' => [
                        'saturday',
                    ],
                    'opens' => '19:00',
                    'closes' => '19:00',
                ],
            ],
            $jsonPlace->openingHours
        );
    }

    /**
     * @test
     */
    public function it_handles_places_with_contact_info(): void
    {
        $jsonPlace = $this->createJsonPlaceFromCdbXml('place_with_contact_info.xml', '3.3');

        $this->assertEquals('info@ouddommelhof.be', $jsonPlace->bookingInfo['email']);
        $this->assertEquals(['+32 11 63 23 40'], $jsonPlace->contactPoint['phone']);
        $this->assertEquals(['http://www.ouddommelhof.be'], $jsonPlace->contactPoint['url']);
    }
}
