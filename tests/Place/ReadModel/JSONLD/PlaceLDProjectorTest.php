<?php

namespace CultuurNet\UDB3\Place\ReadModel\JSONLD;

use Broadway\Domain\DateTime;
use Broadway\Domain\Metadata;
use Broadway\Serializer\SerializerInterface;
use CommerceGuys\Intl\Currency\CurrencyRepository;
use CommerceGuys\Intl\NumberFormat\NumberFormatRepository;
use CultureFeed_Cdb_Data_File;
use CultuurNet\Geocoding\Coordinate\Coordinates;
use CultuurNet\Geocoding\Coordinate\Latitude;
use CultuurNet\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarFactory;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Cdb\PriceDescriptionParser;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ReadModel\DocumentRepositoryInterface;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Media\Serialization\MediaObjectSerializer;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXmlContactInfoImporter;
use CultuurNet\UDB3\Offer\ReadModel\JSONLD\CdbXMLItemBaseImporter;
use CultuurNet\UDB3\OfferLDProjectorTestBase;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Place\Events\LabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved;
use CultuurNet\UDB3\Place\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Place\Events\MarkedAsCanonical;
use CultuurNet\UDB3\Place\Events\MarkedAsDuplicate;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\PlaceDeleted;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Events\PlaceUpdatedFromUDB2;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ReadModel\JsonDocumentLanguageEnricher;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use stdClass;
use ValueObjects\Geography\Country;

class PlaceLDProjectorTest extends OfferLDProjectorTestBase
{
    /**
     * @var PlaceLDProjector
     */
    protected $projector;

    /**
     * @var DocumentRepositoryInterface|MockObject
     */
    protected $documentRepository;

    /**
     * @var IriGeneratorInterface
     */
    private $iriGenerator;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var Address
     */
    private $address;

    /**
     * @var CdbXMLImporter
     */
    private $cdbXMLImporter;

    /**
     * @var IriGeneratorInterface
     */
    private $mediaIriGenerator;

    /**
     * Constructs a test case with the given name.
     *
     * @param string $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct($name, $data, $dataName, 'CultuurNet\\UDB3\\Place');
    }

    /**
     * @inheritdoc
     */
    public function setUp()
    {
        parent::setUp();

        $this->iriGenerator = new CallableIriGenerator(
            function ($id) {
                return 'http://example.com/entity/' . $id;
            }
        );

        $this->serializer = new MediaObjectSerializer($this->iriGenerator);

        $this->mediaIriGenerator = new CallableIriGenerator(function (CultureFeed_Cdb_Data_File $file) {
            return 'http://example.com/media/' . $file->getFileName();
        });

        $this->cdbXMLImporter = new CdbXMLImporter(
            new CdbXMLItemBaseImporter(
                new PriceDescriptionParser(
                    new NumberFormatRepository(),
                    new CurrencyRepository()
                ),
                [
                    'nl' => 'Basistarief',
                    'fr' => 'Tarif de base',
                    'en' => 'Base tarif',
                    'de' => 'Basisrate',
                ]
            ),
            new CalendarFactory(),
            new CdbXmlContactInfoImporter()
        );

        $this->projector = new PlaceLDProjector(
            $this->documentRepository,
            $this->iriGenerator,
            $this->organizerService,
            $this->serializer,
            $this->cdbXMLImporter,
            new JsonDocumentLanguageEnricher(
                new PlaceJsonDocumentLanguageAnalyzer()
            ),
            [
                'nl' => 'Basistarief',
                'fr' => 'Tarif de base',
                'en' => 'Base tariff',
                'de' => 'Basisrate',
            ]
        );

        $street = new Street('Kerkstraat 69');
        $locality = new Locality('Leuven');
        $postalCode = new PostalCode('3000');
        $country = Country::fromNative('BE');

        $this->address = new Address($street, $postalCode, $locality, $country);
    }

    /**
     * @test
     */
    public function it_handles_new_places_without_theme()
    {
        $id = 'foo';
        $created = '2015-01-20T13:25:21+01:00';

        $placeCreated = new PlaceCreated(
            $id,
            new Language('en'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            $this->address,
            new Calendar(CalendarType::PERMANENT())
        );

        $jsonLD = new stdClass();
        $jsonLD->{'@id'} = 'http://example.com/entity/' . $id;
        $jsonLD->{'@context'} = '/contexts/place';
        $jsonLD->mainLanguage = 'en';
        $jsonLD->name = (object)[ 'en' => 'some representative title' ];
        $jsonLD->address = (object) [
            'en' => (object) [
                'addressCountry' => 'BE',
                'addressLocality' => 'Leuven',
                'postalCode' => '3000',
                'streetAddress' => 'Kerkstraat 69',
            ],
        ];
        $jsonLD->calendarType = 'permanent';
        $jsonLD->availableTo = '2100-01-01T00:00:00+00:00';
        $jsonLD->terms = [
            (object)[
                'id' => '0.50.4.0.0',
                'label' => 'concert',
                'domain' => 'eventtype',
            ],
        ];
        $jsonLD->created = $created;
        $jsonLD->modified = $created;
        $jsonLD->workflowStatus = 'DRAFT';
        $jsonLD->languages = ['en'];
        $jsonLD->completedLanguages = ['en'];

        $body = $this->project(
            $placeCreated,
            $id,
            null,
            DateTime::fromString($created)
        );

        $this->assertEquals(
            $jsonLD,
            $body
        );
    }

    /**
     * @test
     */
    public function it_handles_new_places_with_theme()
    {
        $id = 'bar';
        $created = '2015-01-20T13:25:21+01:00';

        $placeCreated = new PlaceCreated(
            $id,
            new Language('en'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            $this->address,
            new Calendar(CalendarType::PERMANENT()),
            new Theme('123', 'theme label')
        );

        $jsonLD = new stdClass();
        $jsonLD->{'@id'} = 'http://example.com/entity/' . $id;
        $jsonLD->{'@context'} = '/contexts/place';
        $jsonLD->mainLanguage = 'en';
        $jsonLD->name = (object)[ 'en' => 'some representative title' ];
        $jsonLD->address = (object) [
            'en' => (object) [
                'addressCountry' => 'BE',
                'addressLocality' => 'Leuven',
                'postalCode' => '3000',
                'streetAddress' => 'Kerkstraat 69',
            ],
        ];
        $jsonLD->calendarType = 'permanent';
        $jsonLD->availableTo = '2100-01-01T00:00:00+00:00';
        $jsonLD->terms = [
            (object)[
                'id' => '0.50.4.0.0',
                'label' => 'concert',
                'domain' => 'eventtype',
            ],
            (object)[
                'id' => '123',
                'label' => 'theme label',
                'domain' => 'theme',
            ],
        ];
        $jsonLD->created = $created;
        $jsonLD->modified = $created;
        $jsonLD->workflowStatus = 'DRAFT';
        $jsonLD->languages = ['en'];
        $jsonLD->completedLanguages = ['en'];

        $body = $this->project(
            $placeCreated,
            $id,
            null,
            DateTime::fromString($created)
        );

        $this->assertEquals(
            $jsonLD,
            $body
        );
    }

    /**
     * @test
     */
    public function it_handles_new_places_with_creator()
    {
        $id = 'foo';
        $created = '2015-01-20T13:25:21+01:00';

        $placeCreated = new PlaceCreated(
            $id,
            new Language('en'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            $this->address,
            new Calendar(CalendarType::PERMANENT())
        );

        $jsonLD = new stdClass();
        $jsonLD->{'@id'} = 'http://example.com/entity/' . $id;
        $jsonLD->{'@context'} = '/contexts/place';
        $jsonLD->mainLanguage = 'en';
        $jsonLD->name = (object) ['en' => 'some representative title'];
        $jsonLD->address = (object) [
            'en' => (object) [
                'addressCountry' => 'BE',
                'addressLocality' => 'Leuven',
                'postalCode' => '3000',
                'streetAddress' => 'Kerkstraat 69',
            ],
        ];
        $jsonLD->calendarType = 'permanent';
        $jsonLD->terms = [
            (object)[
                'id' => '0.50.4.0.0',
                'label' => 'concert',
                'domain' => 'eventtype',
            ],
        ];
        $jsonLD->created = $created;
        $jsonLD->modified = $created;
        $jsonLD->creator = '20a72430-7e3e-4b75-ab59-043156b3169c';
        $jsonLD->workflowStatus = 'DRAFT';
        $jsonLD->availableTo = '2100-01-01T00:00:00+00:00';
        $jsonLD->languages = ['en'];
        $jsonLD->completedLanguages = ['en'];

        $metadata = new Metadata(
            [
                'user_id' => '20a72430-7e3e-4b75-ab59-043156b3169c',
                'user_nick' => 'Tester',
            ]
        );

        $actualJsonLD = $this->project(
            $placeCreated,
            $id,
            $metadata,
            DateTime::fromString($created)
        );

        $this->assertEquals($jsonLD, $actualJsonLD);
    }

    /**
     * @test
     */
    public function it_should_project_an_updated_address()
    {
        $jsonLD = new stdClass();
        $jsonLD->{'@id'} = 'http://io.uitdatabank.be/place/66f30742-dee9-4794-ac92-fa44634692b8';
        $jsonLD->mainLanguage = 'nl';
        $jsonLD->name = (object) ['nl'=>'some representative title'];
        $jsonLD->address = (object) [
            'nl' => (object) [
                'addressCountry' => '$country',
                'addressLocality' => '$locality',
                'postalCode' => '$postalCode',
                'streetAddress' => '$street',
            ],
        ];
        $jsonLD->calendarType = 'permanent';
        $jsonLD->terms = [
            (object)[
                'id' => '0.50.4.0.0',
                'label' => 'concert',
                'domain' => 'eventtype',
            ],
        ];
        $jsonLD->languages = ['nl'];
        $jsonLD->completedLanguages = ['nl'];

        $initialDocument = (new JsonDocument('66f30742-dee9-4794-ac92-fa44634692b8'))
            ->withBody($jsonLD);

        $this->documentRepository->save($initialDocument);

        $expectedJsonLD = new stdClass();
        $expectedJsonLD->{'@id'} = 'http://io.uitdatabank.be/place/66f30742-dee9-4794-ac92-fa44634692b8';
        $expectedJsonLD->mainLanguage = 'nl';
        $expectedJsonLD->name = (object) ['nl'=>'some representative title'];
        $expectedJsonLD->address = (object) [
            'nl' => (object) [
                'addressCountry' => 'BE',
                'addressLocality' => 'Kessel-lo',
                'postalCode' => '3010',
                'streetAddress' => 'Eenmeilaan 35',
            ],
        ];
        $expectedJsonLD->calendarType = 'permanent';
        $expectedJsonLD->terms = [
            (object)[
                'id' => '0.50.4.0.0',
                'label' => 'concert',
                'domain' => 'eventtype',
            ],
        ];
        $expectedJsonLD->languages = ['nl'];
        $expectedJsonLD->completedLanguages = ['nl'];
        $expectedJsonLD->modified = $this->recordedOn->toString();

        $addressUpdated = new AddressUpdated(
            '66f30742-dee9-4794-ac92-fa44634692b8',
            new Address(
                new Street('Eenmeilaan 35'),
                new PostalCode('3010'),
                new Locality('Kessel-lo'),
                Country::fromNative('BE')
            )
        );

        $body = $this->project(
            $addressUpdated,
            '66f30742-dee9-4794-ac92-fa44634692b8',
            null,
            $this->recordedOn->toBroadwayDateTime()
        );

        $this->assertEquals($expectedJsonLD, $body);
    }

    /**
     * @test
     */
    public function it_should_project_a_translated_address()
    {
        $jsonLD = new stdClass();
        $jsonLD->{'@id'} = 'http://io.uitdatabank.be/place/66f30742-dee9-4794-ac92-fa44634692b8';
        $jsonLD->mainLanguage = 'nl';
        $jsonLD->name = (object) ['nl'=>'some representative title'];
        $jsonLD->address = (object) [
            'nl' => (object) [
                'addressCountry' => '$country',
                'addressLocality' => '$locality',
                'postalCode' => '$postalCode',
                'streetAddress' => '$street',
            ],
        ];
        $jsonLD->calendarType = 'permanent';
        $jsonLD->terms = [
            (object)[
                'id' => '0.50.4.0.0',
                'label' => 'concert',
                'domain' => 'eventtype',
            ],
        ];
        $jsonLD->languages = ['nl'];
        $jsonLD->completedLanguages = ['nl'];

        $initialDocument = (new JsonDocument('66f30742-dee9-4794-ac92-fa44634692b8'))
            ->withBody($jsonLD);

        $this->documentRepository->save($initialDocument);

        $expectedJsonLD = new stdClass();
        $expectedJsonLD->{'@id'} = 'http://io.uitdatabank.be/place/66f30742-dee9-4794-ac92-fa44634692b8';
        $expectedJsonLD->mainLanguage = 'nl';
        $expectedJsonLD->name = (object) ['nl'=>'some representative title'];
        $expectedJsonLD->address = (object) [
            'nl' => (object) [
                'addressCountry' => '$country',
                'addressLocality' => '$locality',
                'postalCode' => '$postalCode',
                'streetAddress' => '$street',
            ],
            'fr' => (object) [
                'addressCountry' => 'BE',
                'addressLocality' => 'Kessel-lo',
                'postalCode' => '3010',
                'streetAddress' => 'Eenmeilaan 35',
            ],
        ];
        $expectedJsonLD->calendarType = 'permanent';
        $expectedJsonLD->terms = [
            (object)[
                'id' => '0.50.4.0.0',
                'label' => 'concert',
                'domain' => 'eventtype',
            ],
        ];
        $expectedJsonLD->languages = ['nl', 'fr'];
        $expectedJsonLD->completedLanguages = ['nl'];
        $expectedJsonLD->modified = $this->recordedOn->toString();

        $addressTranslated = new AddressTranslated(
            '66f30742-dee9-4794-ac92-fa44634692b8',
            new Address(
                new Street('Eenmeilaan 35'),
                new PostalCode('3010'),
                new Locality('Kessel-lo'),
                Country::fromNative('BE')
            ),
            new Language('fr')
        );

        $body = $this->project(
            $addressTranslated,
            '66f30742-dee9-4794-ac92-fa44634692b8',
            null,
            $this->recordedOn->toBroadwayDateTime()
        );

        $this->assertEquals($expectedJsonLD, $body);
    }

    /**
     * @test
     */
    public function it_should_set_a_main_language_when_importing_from_udb2()
    {
        $event = $this->placeImportedFromUDB2('place_with_short_and_long_description.cdbxml.xml');

        $body = $this->project($event, $event->getActorId());

        $this->assertEquals('nl', $body->mainLanguage);
    }

    /**
     * @test
     */
    public function it_should_not_update_the_main_language_when_updating_from_udb2()
    {
        // First make sure there is a new place created.
        $placeId = 'foo';
        $created = '2015-01-20T13:25:21+01:00';
        $placeCreated = new PlaceCreated(
            $placeId,
            new Language('en'),
            new Title('some representative title'),
            new EventType('0.50.4.0.0', 'concert'),
            $this->address,
            new Calendar(CalendarType::PERMANENT())
        );
        $this->project(
            $placeCreated,
            $placeId,
            null,
            DateTime::fromString($created)
        );

        // Now do the real update.
        $place = $this->placeUpdatedFromUDB2('place_with_short_and_long_description.cdbxml.xml');

        $body = $this->project($place, $placeId);

        $this->assertEquals(new Language('en'), $body->mainLanguage);
    }

    /**
     * @test
     */
    public function it_does_not_add_an_empty_image_property()
    {
        $event = $this->placeImportedFromUDB2('place_without_image.cdbxml.xml');

        $body = $this->project($event, $event->getActorId());

        $this->assertObjectNotHasAttribute('image', $body);
    }

    /**
     * @return array
     */
    public function descriptionSamplesProvider()
    {
        $samples = array(
            ['place_with_short_description.cdbxml.xml', 'Korte beschrijving.'],
            ['place_with_long_description.cdbxml.xml', 'Lange beschrijving.'],
            ['place_with_short_and_long_description.cdbxml.xml', "Korte beschrijving.\n\nLange beschrijving."],
        );

        return $samples;
    }

    /**
     * @test
     */
    public function it_updates_a_place_from_udb2()
    {
        $placeImportedFromUdb2 = $this->placeImportedFromUDB2('place_with_short_description.cdbxml.xml');
        $actorId = $placeImportedFromUdb2->getActorId();

        $cdbXml = file_get_contents(__DIR__ . '/place_with_short_and_long_description.cdbxml.xml');
        $placeUpdatedFromUdb2 = new PlaceUpdatedFromUDB2(
            $actorId,
            $cdbXml,
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $body = $this->project($placeUpdatedFromUdb2, $actorId);

        $this->assertEquals("Korte beschrijving.\n\nLange beschrijving.", $body->description->nl);
    }

    /**
     * @test
     */
    public function it_updates_a_place_from_udb2_when_it_has_been_deleted_in_udb3()
    {
        $placeImportedFromUdb2 = $this->placeImportedFromUDB2('place_with_short_description.cdbxml.xml');
        $actorId = $placeImportedFromUdb2->getActorId();

        $placeDeleted = new PlaceDeleted($actorId);
        $this->project($placeDeleted, $actorId, null, null, false);

        $cdbXml = file_get_contents(__DIR__ . '/place_with_short_and_long_description.cdbxml.xml');
        $placeUpdatedFromUdb2 = new PlaceUpdatedFromUDB2(
            $actorId,
            $cdbXml,
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $body = $this->project($placeUpdatedFromUdb2, $actorId);

        $this->assertEquals("Korte beschrijving.\n\nLange beschrijving.", $body->description->nl);

    }

    /**
     * @test
     * @dataProvider descriptionSamplesProvider
     * @param string $fileName
     * @param string $expectedDescription
     */
    public function it_adds_a_description_property_when_cdbxml_has_long_or_short_description(
        $fileName,
        $expectedDescription
    ) {
        $event = $this->placeImportedFromUDB2($fileName);

        $body = $this->project($event, $event->getActorId());

        $this->assertEquals(
            $expectedDescription,
            $body->description->nl
        );
    }

    /**
     * @test
     */
    public function it_should_keep_address_translations_when_updating_from_cdbxml()
    {
        $initialJsonLd = new stdClass();
        $initialJsonLd->{'@id'} = 'http://io.uitdatabank.be/place/66f30742-dee9-4794-ac92-fa44634692b8';
        $initialJsonLd->mainLanguage = 'nl';
        $initialJsonLd->name = (object) ['nl'=>'some representative title'];
        $initialJsonLd->address = (object) [
            'nl' => (object) [
                'addressCountry' => 'BE',
                'addressLocality' => 'Brussel',
                'postalCode' => '1000',
                'streetAddress' => 'Wetstraat 1',
            ],
            'fr' => (object) [
                'addressCountry' => 'BE',
                'addressLocality' => 'Bruxelles',
                'postalCode' => '1000',
                'streetAddress' => 'Rue de la loi 1',
            ],
        ];
        $initialJsonLd->calendarType = 'permanent';
        $initialJsonLd->terms = [
            (object)[
                'id' => '0.50.4.0.0',
                'label' => 'concert',
                'domain' => 'eventtype',
            ],
        ];
        $initialJsonLd->languages = ['nl', 'fr'];
        $initialJsonLd->completedLanguages = ['nl'];

        $initialDocument = (new JsonDocument('66f30742-dee9-4794-ac92-fa44634692b8'))
            ->withBody($initialJsonLd);

        $this->documentRepository->save($initialDocument);

        $expectedJsonLdAddress = (object) [
            'nl' => (object) [
                'addressCountry' => 'BE',
                'addressLocality' => 'Overpelt',
                'postalCode' => '3900',
                'streetAddress' => 'Jeugdlaan 2',
            ],
            'fr' => (object) [
                'addressCountry' => 'BE',
                'addressLocality' => 'Bruxelles',
                'postalCode' => '1000',
                'streetAddress' => 'Rue de la loi 1',
            ],
        ];

        $cdbXml = file_get_contents(__DIR__ . '/place_with_long_description.cdbxml.xml');
        $placeUpdatedFromUdb2 = new PlaceUpdatedFromUDB2(
            '66f30742-dee9-4794-ac92-fa44634692b8',
            $cdbXml,
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $actualJsonLd = $this->project($placeUpdatedFromUdb2, '66f30742-dee9-4794-ac92-fa44634692b8');
        $this->assertEquals($expectedJsonLdAddress, $actualJsonLd->address);
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_major_info()
    {
        $id = 'foo';
        $title = new Title('new title');
        $eventType = new EventType('0.50.4.0.1', 'concertnew');
        $calendar = new Calendar(
            CalendarType::SINGLE(),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-01-26T13:25:21+01:00'),
            \DateTime::createFromFormat(\DateTime::ATOM, '2015-02-26T13:25:21+01:00')
        );
        $theme = new Theme('123', 'theme label');
        $majorInfoUpdated = new MajorInfoUpdated($id, $title, $eventType, $this->address, $calendar, $theme);

        $jsonLD = new stdClass();
        $jsonLD->{'@id'} = 'http://io.uitdatabank.be/place/foo';
        $jsonLD->mainLanguage = 'en';
        $jsonLD->name = (object)['en'=>'some representative title'];
        $jsonLD->address = (object) [
            'en' => (object) [
                'addressCountry' => '$country',
                'addressLocality' => '$locality',
                'postalCode' => '$postalCode',
                'streetAddress' => '$street',
            ],
        ];
        $jsonLD->calendarType = 'permanent';
        $jsonLD->terms = [
            (object)[
                'id' => '0.50.4.0.0',
                'label' => 'concert',
                'domain' => 'eventtype',
            ],
        ];
        $jsonLD->languages = ['en'];
        $jsonLD->completedLanguages = ['en'];

        $initialDocument = (new JsonDocument('foo'))
            ->withBody($jsonLD);

        $this->documentRepository->save($initialDocument);

        $expectedJsonLD = new stdClass();
        $expectedJsonLD->{'@id'} = 'http://io.uitdatabank.be/place/foo';
        $expectedJsonLD->mainLanguage = 'en';
        $expectedJsonLD->name = (object)['en'=>'new title'];
        $expectedJsonLD->address = (object) [
            'en' => (object) [
                'addressCountry' => 'BE',
                'addressLocality' => 'Leuven',
                'postalCode' => '3000',
                'streetAddress' => 'Kerkstraat 69',
            ],
        ];
        $expectedJsonLD->calendarType = 'single';
        $expectedJsonLD->terms = [
            (object)[
                'id' => '0.50.4.0.1',
                'label' => 'concertnew',
                'domain' => 'eventtype',
            ],
            (object)[
                'id' => '123',
                'label' => 'theme label',
                'domain' => 'theme',
            ],
        ];
        $expectedJsonLD->startDate = '2015-01-26T13:25:21+01:00';
        $expectedJsonLD->endDate = '2015-02-26T13:25:21+01:00';
        $expectedJsonLD->availableTo = $expectedJsonLD->endDate;
        $expectedJsonLD->languages = ['en'];
        $expectedJsonLD->completedLanguages = ['en'];
        $expectedJsonLD->modified = $this->recordedOn->toString();

        $body = $this->project(
            $majorInfoUpdated,
            $majorInfoUpdated->getPlaceId(),
            null,
            $this->recordedOn->toBroadwayDateTime()
        );

        $this->assertEquals($expectedJsonLD, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_geo_coordinates()
    {
        $id = 'ea328f14-a3c8-4f71-abd9-00cd0a2cf217';

        $initialDocument = new JsonDocument(
            $id,
            json_encode(
                [
                    '@id' => 'http://uitdatabank/place/' . $id,
                    '@type' => 'Place',
                    'name' => [
                        'nl' => 'Test',
                    ],
                    'languages' => ['nl'],
                    'completedLanguages' => ['nl'],
                ]
            )
        );

        $this->documentRepository->save($initialDocument);

        $coordinatesUpdated = new GeoCoordinatesUpdated(
            $id,
            new Coordinates(
                new Latitude(1.1234567),
                new Longitude(-0.34567)
            )
        );

        $expectedBody = (object) [
            '@id' => 'http://uitdatabank/place/' . $id,
            '@type' => 'Place',
            'name' => (object) ['nl' => 'Test'],
            'languages' => ['nl'],
            'completedLanguages' => ['nl'],
            'geo' => (object) [
                'latitude' => 1.1234567,
                'longitude' => -0.34567,
            ],
            'modified' => $this->recordedOn->toString(),
        ];

        $body = $this->project($coordinatesUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());
        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_updates_workflow_status_on_delete()
    {
        $placeId = 'ea328f14-a3c8-4f71-abd9-00cd0a2cf217';

        $placeDeleted = new PlaceDeleted($placeId);

        $body = $this->project($placeDeleted, $placeId, null, $this->recordedOn->toBroadwayDateTime());

        $expectedJson = (object) [
            '@id' => 'http://example.com/entity/' . $placeId,
            '@context' => '/contexts/place',
            'workflowStatus' => 'DELETED',
            'modified' => $this->recordedOn->toString(),
        ];

        $this->assertEquals($expectedJson, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_addition_of_a_label()
    {
        $labelAdded = new LabelAdded(
            'foo',
            new Label('label B')
        );

        $initialDocument = new JsonDocument(
            'foo',
            json_encode([
                'labels' => ['label A'],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $body = $this->project($labelAdded, 'foo');

        $this->assertEquals(
            ['label A', 'label B'],
            $body->labels
        );
    }

    /**
     * @test
     */
    public function it_projects_the_removal_of_a_label()
    {
        $initialDocument = new JsonDocument(
            'foo',
            json_encode([
                'labels' => ['label A', 'label B', 'label C'],
            ])
        );

        $this->documentRepository->save($initialDocument);

        $labelRemoved = new LabelRemoved(
            'foo',
            new Label('label B')
        );

        $body = $this->project($labelRemoved, 'foo');

        $this->assertEquals(
            ['label A', 'label C'],
            $body->labels
        );
    }

    /**
     * @test
     */
    public function it_projects_the_addition_of_a_label_to_a_place_without_existing_labels()
    {
        $initialDocument = new JsonDocument(
            'foo',
            json_encode([
                'bar' => 'stool',
            ])
        );

        $this->documentRepository->save($initialDocument);

        $labelAdded = new LabelAdded(
            'foo',
            new Label('label B')
        );

        $body = $this->project($labelAdded, 'foo', null, $this->recordedOn->toBroadwayDateTime());

        $expectedBody = new stdClass();
        $expectedBody->bar = 'stool';
        $expectedBody->labels = ['label B'];
        $expectedBody->modified = $this->recordedOn->toString();

        $this->assertEquals(
            $expectedBody,
            $body
        );
    }

    /**
     * @test
     */
    public function it_removes_geocoordinates_after_major_info_updated()
    {
        $initialDocument = new JsonDocument(
            '3c4850d7-689a-4729-8c5f-5f6c172ba52d',
            json_encode(
                [
                    'name' => [
                        'nl' => 'Old title',
                    ],
                    'geo' => [
                        'latitude' => 1.5678,
                        'longitude' => -0.9524,
                    ],
                    'terms' => [],
                    'languages' => ['nl'],
                    'completedLanguages' => ['nl'],
                ]
            )
        );

        $this->documentRepository->save($initialDocument);

        $majorInfoUpdated = new MajorInfoUpdated(
            '3c4850d7-689a-4729-8c5f-5f6c172ba52d',
            new Title('New title'),
            new EventType('1.0.0.0', 'Mock'),
            new Address(
                new Street('Natieplein 2'),
                new PostalCode('1000'),
                new Locality('Brussel'),
                Country::fromNative('BE')
            ),
            new Calendar(CalendarType::PERMANENT())
        );

        $body = $this->project($majorInfoUpdated, '3c4850d7-689a-4729-8c5f-5f6c172ba52d');

        $this->assertArrayNotHasKey('geo', (array) $body);
    }

    /**
     * @test
     */
    public function it_removes_geocoordinates_after_place_updated_from_udb2()
    {
        $initialDocument = new JsonDocument(
            '318F2ACB-F612-6F75-0037C9C29F44087A',
            json_encode(
                [
                    'name' => [
                        'nl' => 'Old title',
                    ],
                    'geo' => [
                        'latitude' => 1.5678,
                        'longitude' => -0.9524,
                    ],
                    'terms' => [],
                    'languages' => ['nl'],
                    'completedLanguages' => ['nl'],
                ]
            )
        );

        $this->documentRepository->save($initialDocument);

        $cdbXml = file_get_contents(__DIR__ . '/place_with_long_description.cdbxml.xml');
        $placeUpdatedFromUdb2 = new PlaceUpdatedFromUDB2(
            '318F2ACB-F612-6F75-0037C9C29F44087A',
            $cdbXml,
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        $body = $this->project($placeUpdatedFromUdb2, '318F2ACB-F612-6F75-0037C9C29F44087A');

        $this->assertArrayNotHasKey('geo', (array) $body);
    }

    /**
     * @test
     */
    public function it_adds_the_canonical_id_to_a_place_that_is_marked_as_a_duplicate()
    {
        $duplicateId = 'cb0f0523-ccd9-4a8e-b985-820e40ca5d40';
        $canonicalId = '6caefd7d-9e2f-4b61-a7fa-54d8b25fb8d9';

        $initialDocument = new JsonDocument(
            $duplicateId,
            json_encode(
                [
                    'name' => [
                        'nl' => 'Old title',
                    ],
                    'geo' => [
                        'latitude' => 1.5678,
                        'longitude' => -0.9524,
                    ],
                    'terms' => [],
                    'languages' => ['nl'],
                    'completedLanguages' => ['nl'],
                ]
            )
        );

        $this->documentRepository->save($initialDocument);

        $markedAsDuplicate = new MarkedAsDuplicate($duplicateId, $canonicalId);

        $body = $this->project($markedAsDuplicate, $duplicateId);

        $this->assertArrayHasKey('duplicateOf', (array) $body);
        $this->assertEquals('http://example.com/entity/' . $canonicalId, $body->duplicateOf);
    }

    /**
     * @test
     */
    public function it_adds_the_duplicate_id_to_a_place_that_is_marked_as_canonical()
    {
        $duplicateId = 'cb0f0523-ccd9-4a8e-b985-820e40ca5d40';
        $canonicalId = '6caefd7d-9e2f-4b61-a7fa-54d8b25fb8d9';

        $initialDocument = new JsonDocument(
            $canonicalId,
            json_encode(
                [
                    'name' => [
                        'nl' => 'Old title',
                    ],
                    'geo' => [
                        'latitude' => 1.5678,
                        'longitude' => -0.9524,
                    ],
                    'terms' => [],
                    'languages' => ['nl'],
                    'completedLanguages' => ['nl'],
                ]
            )
        );

        $this->documentRepository->save($initialDocument);

        $markedAsCanonical = new MarkedAsCanonical($canonicalId, $duplicateId);

        $body = $this->project($markedAsCanonical, $canonicalId);

        $this->assertArrayHasKey('duplicatedBy', (array) $body);
        $this->assertEquals(
            [
                'http://example.com/entity/' . $duplicateId,
            ],
            $body->duplicatedBy
        );
    }

    /**
     * @test
     */
    public function it_adds_the_duplicate_id_to_a_place_that_is_marked_as_canonical_and_already_had_duplicates()
    {
        $duplicateId = 'cb0f0523-ccd9-4a8e-b985-820e40ca5d40';
        $canonicalId = '6caefd7d-9e2f-4b61-a7fa-54d8b25fb8d9';

        $initialDocument = new JsonDocument(
            $canonicalId,
            json_encode(
                [
                    'name' => [
                        'nl' => 'Old title',
                    ],
                    'geo' => [
                        'latitude' => 1.5678,
                        'longitude' => -0.9524,
                    ],
                    'terms' => [],
                    'languages' => ['nl'],
                    'completedLanguages' => ['nl'],
                    'duplicatedBy' => [
                        'http://example.com/entity/fb674ae3-c024-43a7-8956-643b10667443',
                        'http://example.com/entity/653d4650-10eb-4862-806f-baf699034064',
                    ],
                ]
            )
        );

        $this->documentRepository->save($initialDocument);

        $markedAsCanonical = new MarkedAsCanonical($canonicalId, $duplicateId);

        $body = $this->project($markedAsCanonical, $canonicalId);

        $this->assertArrayHasKey('duplicatedBy', (array) $body);
        $this->assertEquals(
            [
                'http://example.com/entity/fb674ae3-c024-43a7-8956-643b10667443',
                'http://example.com/entity/653d4650-10eb-4862-806f-baf699034064',
                'http://example.com/entity/' . $duplicateId,
            ],
            $body->duplicatedBy
        );
    }

    /**
     * @param string $fileName
     * @return PlaceImportedFromUDB2
     */
    private function placeImportedFromUDB2($fileName)
    {
        $cdbXml = file_get_contents(
            __DIR__ . '/' . $fileName
        );
        $event = new PlaceImportedFromUDB2(
            'someId',
            $cdbXml,
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        return $event;
    }

    /**
     * @param string $fileName
     * @return PlaceUpdatedFromUDB2
     */
    private function placeUpdatedFromUDB2($fileName)
    {
        $cdbXml = file_get_contents(
            __DIR__ . '/' . $fileName
        );
        $event = new PlaceUpdatedFromUDB2(
            'someId',
            $cdbXml,
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        return $event;
    }
}
