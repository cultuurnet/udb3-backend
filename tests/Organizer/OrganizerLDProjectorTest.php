<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventBus;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Actor\ActorEvent;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Label;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Organizer\Events\AddressRemoved;
use CultuurNet\UDB3\Organizer\Events\AddressTranslated;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerDeleted;
use CultuurNet\UDB3\Organizer\Events\OrganizerEvent;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\TitleTranslated;
use CultuurNet\UDB3\Organizer\Events\TitleUpdated;
use CultuurNet\UDB3\Organizer\Events\WebsiteUpdated;
use CultuurNet\UDB3\Organizer\ReadModel\JSONLD\OrganizerJsonDocumentLanguageAnalyzer;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ReadModel\JsonDocumentLanguageEnricher;
use CultuurNet\UDB3\RecordedOn;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;
use ValueObjects\Web\Url;

class OrganizerLDProjectorTest extends TestCase
{
    protected OrganizerLDProjector $projector;

    /**
     * @var DocumentRepository|MockObject
     */
    protected $documentRepository;

    private RecordedOn $recordedOn;

    public function setUp(): void
    {
        $this->documentRepository = $this->createMock(DocumentRepository::class);

        $eventBus = $this->createMock(EventBus::class);

        $iriGenerator = new CallableIriGenerator(
            function ($id) {
                return 'http://example.com/entity/' . $id;
            }
        );

        $this->projector = new OrganizerLDProjector(
            $this->documentRepository,
            $iriGenerator,
            $eventBus,
            new JsonDocumentLanguageEnricher(
                new OrganizerJsonDocumentLanguageAnalyzer()
            )
        );

        $this->recordedOn = RecordedOn::fromBroadwayDateTime(
            DateTime::fromString('2018-01-18T13:57:09Z')
        );
    }

    private function organizerImportedFromUDB2(string $fileName): OrganizerImportedFromUDB2
    {
        $cdbXml = file_get_contents(
            __DIR__ . '/Samples/' . $fileName
        );

        $event = new OrganizerImportedFromUDB2(
            'someId',
            $cdbXml,
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );

        return $event;
    }

    private function organizerUpdatedFromUDB2(string $fileName): OrganizerUpdatedFromUDB2
    {
        $cdbXml = file_get_contents(
            __DIR__ . '/Samples/' . $fileName
        );

        return new OrganizerUpdatedFromUDB2(
            'someId',
            $cdbXml,
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );
    }

    /**
     * @test
     */
    public function it_handles_new_organizers(): void
    {
        $uuidGenerator = new Version4Generator();
        $id = $uuidGenerator->generate();

        $street = new Street('Kerkstraat 69');
        $locality = new Locality('Leuven');
        $postalCode = new PostalCode('3000');
        $country = Country::fromNative('BE');

        $organizerCreated = new OrganizerCreated(
            $id,
            new Title('some representative title'),
            [new Address($street, $postalCode, $locality, $country)],
            ['050/123'],
            ['test@test.be', 'test2@test.be'],
            ['http://www.google.be']
        );

        $jsonLD = new \stdClass();
        $jsonLD->{'@id'} = 'http://example.com/entity/' . $id;
        $jsonLD->{'@context'} = '/contexts/organizer';

        $jsonLD->mainLanguage = 'nl';
        $jsonLD->name[$jsonLD->mainLanguage] = 'some representative title';

        $jsonLD->address = [
            'nl' => [
                'addressCountry' => $country->getCode()->toNative(),
                'addressLocality' => $locality->toNative(),
                'postalCode' => $postalCode->toNative(),
                'streetAddress' => $street->toNative(),
            ],
        ];
        $jsonLD->phone = ['050/123'];
        $jsonLD->email = ['test@test.be', 'test2@test.be'];
        $jsonLD->url = ['http://www.google.be'];
        $jsonLD->created = $this->recordedOn->toString();
        $jsonLD->creator = '28f69301-13bc-4153-a9d2-e91e89cbe156';
        $jsonLD->workflowStatus = 'ACTIVE';
        $jsonLD->languages = ['nl'];
        $jsonLD->completedLanguages = ['nl'];
        $jsonLD->modified = $this->recordedOn->toString();

        $expectedDocument = (new JsonDocument($id))
            ->withBody($jsonLD);

        $this->documentRepository->expects($this->once())
            ->method('save')
            ->with($expectedDocument);

        $this->projector->handle(
            new DomainMessage(
                1,
                1,
                new Metadata(
                    [
                        'user_id' => '28f69301-13bc-4153-a9d2-e91e89cbe156',
                    ]
                ),
                $organizerCreated,
                $this->recordedOn->toBroadwayDateTime()
            )
        );
    }

    /**
     * @test
     */
    public function it_handles_new_organizers_with_unique_website(): void
    {
        $uuidGenerator = new Version4Generator();
        $id = $uuidGenerator->generate();

        $organizerCreated = new OrganizerCreatedWithUniqueWebsite(
            $id,
            new Language('en'),
            Url::fromNative('http://www.stuk.be'),
            new Title('some representative title')
        );

        $jsonLD = new \stdClass();
        $jsonLD->{'@id'} = 'http://example.com/entity/' . $id;
        $jsonLD->{'@context'} = '/contexts/organizer';
        $jsonLD->mainLanguage = 'en';
        $jsonLD->url = 'http://www.stuk.be';
        $jsonLD->name['en'] = 'some representative title';
        $jsonLD->created = $this->recordedOn->toString();
        $jsonLD->creator = '28f69301-13bc-4153-a9d2-e91e89cbe156';
        $jsonLD->workflowStatus = 'ACTIVE';
        $jsonLD->languages = ['en'];
        $jsonLD->completedLanguages = ['en'];
        $jsonLD->modified = $this->recordedOn->toString();

        $expectedDocument = (new JsonDocument($id))
            ->withBody($jsonLD);

        $this->documentRepository->expects($this->once())
            ->method('save')
            ->with($expectedDocument);

        $this->projector->handle(
            new DomainMessage(
                1,
                1,
                new Metadata(
                    [
                        'user_id' => '28f69301-13bc-4153-a9d2-e91e89cbe156',
                    ]
                ),
                $organizerCreated,
                $this->recordedOn->toBroadwayDateTime()
            )
        );
    }

    /**
     * @test
     */
    public function it_handles_website_update(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';
        $website = 'http://www.depot.be';

        $this->mockGet($organizerId, 'organizer.json');

        $domainMessage = $this->createDomainMessage(
            new WebsiteUpdated(
                $organizerId,
                $website
            )
        );

        $this->expectSave($organizerId, 'organizer_with_updated_website.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_title_update(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';
        $title = 'Het Depot';

        $this->mockGet($organizerId, 'organizer.json');

        $domainMessage = $this->createDomainMessage(
            new TitleUpdated(
                $organizerId,
                $title
            )
        );

        $this->expectSave($organizerId, 'organizer_with_updated_title.json');

        $this->projector->handle($domainMessage);
    }

    public function addressUpdatesDataProvider(): array
    {
        return [
            'organizer with former address' => [
                'currentJson' => 'organizer.json',
                'expectedJson' => 'organizer_with_updated_address.json',
            ],
            'organizer without former address' => [
                'currentJson' => 'organizer_without_address.json',
                'expectedJson' => 'organizer_without_address_after_address_update.json',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider addressUpdatesDataProvider
     */
    public function it_handles_address_updated($currentJson, $expectedJson): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, $currentJson);

        $domainMessage = $this->createDomainMessage(
            new AddressUpdated(
                $organizerId,
                new Address(
                    new Street('Martelarenplein'),
                    new PostalCode('3000'),
                    new Locality('Leuven'),
                    Country::fromNative('BE')
                )
            )
        );

        $this->expectSave($organizerId, $expectedJson);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_address_removed(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer_w_address.json');

        $domainMessage = $this->createDomainMessage(
            new AddressRemoved(
                $organizerId
            )
        );

        $this->expectSave($organizerId, 'organizer_with_deleted_address.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_should_set_name_when_importing_from_udb2(): void
    {
        $event = $this->organizerImportedFromUDB2('organizer_with_email.cdbxml.xml');
        $domainMessage = $this->createDomainMessage($event);

        $actualName = null;

        $this->documentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (JsonDocument $document) use (&$actualName) {
                $actualName = $document->getBody()->name;
                return true;
            }));

        $this->projector->handle($domainMessage);

        $this->assertEquals((object) ['nl' => 'DE Studio'], $actualName);
    }

    /**
     * @test
     */
    public function it_should_update_name_when_updating_from_udb2_and_keep_missing_translations(): void
    {
        // First make sure there is an already created organizer.
        $organizerId = 'someId';

        $organizerJson = file_get_contents(__DIR__ . '/Samples/organizer_with_main_language.json');
        $organizerJson = json_decode($organizerJson);
        $organizerJson->name->en = 'English name';
        $organizerJson = json_encode($organizerJson);

        $this->documentRepository->method('fetch')
            ->with($organizerId)
            ->willReturn(new JsonDocument($organizerId, $organizerJson));

        $event = $this->organizerUpdatedFromUDB2('organizer_with_email.cdbxml.xml');
        $domainMessage = $this->createDomainMessage($event);

        $actualName = null;

        $this->documentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (JsonDocument $document) use (&$actualName) {
                $actualName = $document->getBody()->name;
                return true;
            }));

        $this->projector->handle($domainMessage);

        $this->assertEquals(
            (object) [
                'nl' => 'DE Studio',
                'en' => 'English name',
            ],
            $actualName
        );
    }

    /**
     * @test
     */
    public function it_should_set_main_language_when_importing_from_udb2(): void
    {
        $event = $this->organizerImportedFromUDB2('organizer_with_email.cdbxml.xml');
        $domainMessage = $this->createDomainMessage($event);

        $this->documentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (JsonDocument $document) {
                $body = $document->getBody();
                return $body->mainLanguage === 'nl';
            }));

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_should_set_main_language_when_updating_from_udb2(): void
    {
        // First make sure there is an already created organizer.
        $organizerId = 'someId';
        $this->mockGet($organizerId, 'organizer_with_main_language.json');

        $event = $this->organizerUpdatedFromUDB2('organizer_with_email.cdbxml.xml');
        $domainMessage = $this->createDomainMessage($event);

        $this->documentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (JsonDocument $document) {
                $body = $document->getBody();
                return $body->mainLanguage === 'en';
            }));

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_title_translated(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer.json');

        $domainMessage = $this->createDomainMessage(
            new TitleTranslated(
                $organizerId,
                'EssaiOrganisation',
                'fr'
            )
        );

        $this->expectSave($organizerId, 'organizer_with_translated_title.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_address_translated(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer.json');

        $domainMessage = $this->createDomainMessage(
            new AddressTranslated(
                $organizerId,
                new Address(
                    new Street('Rue'),
                    new PostalCode('3010'),
                    new Locality('Kessel-Lo (Louvain)'),
                    Country::fromNative('BE')
                ),
                new Language('fr')
            )
        );

        $this->expectSave($organizerId, 'organizer_with_translated_address.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_translation_of_organizer_with_untranslated_name(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer_untranslated_name.json');

        $domainMessage = $this->createDomainMessage(
            new TitleTranslated(
                $organizerId,
                'EssaiOrganisation',
                'fr'
            )
        );

        $this->expectSave($organizerId, 'organizer_with_translated_title.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_adds_an_email_property_when_cdbxml_has_an_email(): void
    {
        $event = $this->organizerImportedFromUDB2('organizer_with_email.cdbxml.xml');
        $domainMessage = $this->createDomainMessage($event);

        $this->documentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (JsonDocument $document) {
                $body = $document->getBody();

                $emails = $body->contactPoint->email;
                $expectedEmails = [
                    'info@villanella.be',
                ];

                return is_array($emails) &&
                $emails == $expectedEmails;
            }));

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_add_an_email_property_when_cdbxml_has_no_email(): void
    {
        $event = $this->organizerImportedFromUDB2('organizer_without_email.cdbxml.xml');
        $domainMessage = $this->createDomainMessage($event);

        $this->documentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (JsonDocument $document) {
                $body = $document->getBody();

                return empty($body->contactPoint->email);
            }));

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_adds_an_email_property_when_cdbxml_has_multiple_emails(): void
    {
        $event = $this->organizerImportedFromUDB2('organizer_with_emails.cdbxml.xml');
        $domainMessage = $this->createDomainMessage($event);

        $this->documentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (JsonDocument $document) {
                $body = $document->getBody();

                $emails = $body->contactPoint->email;
                $expectedEmails = [
                    'info@villanella.be',
                    'dirk@dirkinc.be',
                ];

                return is_array($emails) &&
                $emails == $expectedEmails;
            }));

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_adds_a_phone_property_when_cdbxml_has_a_phone_number(): void
    {
        $event = $this->organizerImportedFromUDB2('organizer_with_phone_number.cdbxml.xml');
        $domainMessage = $this->createDomainMessage($event);

        $this->documentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (JsonDocument $document) {
                $body = $document->getBody();

                $phones = $body->contactPoint->phone;
                $expectedPhones = [
                    '+32 3 260 96 10',
                ];

                return is_array($phones) && $phones == $expectedPhones;
            }));

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_adds_a_phone_property_when_cdbxml_has_multiple_phone_numbers(): void
    {
        $event = $this->organizerImportedFromUDB2('organizer_with_phone_numbers.cdbxml.xml');
        $domainMessage = $this->createDomainMessage($event);

        $this->documentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (JsonDocument $document) {
                $body = $document->getBody();

                $phones = $body->contactPoint->phone;
                $expectedPhones = [
                    '+32 3 260 96 10',
                    '+32 3 062 69 01',
                ];

                return is_array($phones) && $phones == $expectedPhones;
            }));

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_does_not_add_a_phone_property_when_cdbxml_has_no_phone(): void
    {
        $event = $this->organizerImportedFromUDB2('organizer_without_phone_number.cdbxml.xml');
        $domainMessage = $this->createDomainMessage($event);

        $this->documentRepository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (JsonDocument $document) {
                $body = $document->getBody();

                return empty($body->contactPoint->phone);
            }));

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_updates_workflow_status_on_delete(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer.json');

        $domainMessage = $this->createDomainMessage(
            new OrganizerDeleted($organizerId)
        );

        $this->expectSave($organizerId, 'organizer_with_deleted_workflow_status.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_can_update_an_organizer_from_udb2_even_if_it_has_been_deleted(): void
    {
        $organizerUpdatedFromUdb2 = $this->organizerUpdatedFromUDB2('organizer_with_email.cdbxml.xml');
        $domainMessage = $this->createDomainMessage($organizerUpdatedFromUdb2);
        $actorId = $organizerUpdatedFromUdb2->getActorId();

        $this->documentRepository->expects($this->once())
            ->method('fetch')
            ->with($actorId)
            ->willThrowException(DocumentDoesNotExist::withId($actorId));

        $this->documentRepository->expects($this->once())
            ->method('save')
            ->with(
                $this->callback(
                    function (JsonDocument $jsonDocument) use ($actorId) {
                        return $actorId === $jsonDocument->getId() && !empty($jsonDocument->getRawBody());
                    }
                )
            );

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_label_added(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';
        $label = new Label('labelName', true);

        $this->mockGet($organizerId, 'organizer.json');

        $labelAdded = new LabelAdded($organizerId, $label);
        $domainMessage = $this->createDomainMessage($labelAdded);

        $this->expectSave($organizerId, 'organizer_with_one_label.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_invisible_label_added(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';
        $label = new Label('labelName', false);

        $this->mockGet($organizerId, 'organizer.json');

        $labelAdded = new LabelAdded($organizerId, $label);
        $domainMessage = $this->createDomainMessage($labelAdded);

        $this->expectSave($organizerId, 'organizer_with_one_label_invisible.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     * @dataProvider labelRemovedDataProvider
     */
    public function it_handles_label_removed(
        Label $label,
        string $originalFile,
        string $finalFile
    ): void {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, $originalFile);

        $labelRemoved = new LabelRemoved($organizerId, $label);
        $domainMessage = $this->createDomainMessage($labelRemoved);

        $this->expectSave($organizerId, $finalFile);

        $this->projector->handle($domainMessage);
    }

    public function labelRemovedDataProvider(): array
    {
        return [
            [
                new Label('labelName'),
                'organizer_with_one_label.json',
                'organizer_with_modified.json',
            ],
            [
                new Label('anotherLabel'),
                'organizer_with_two_labels.json',
                'organizer_with_one_label.json',
            ],
            [
                new Label('yetAnotherLabel'),
                'organizer_with_three_labels.json',
                'organizer_with_two_labels.json',
            ],
        ];
    }

    /**
     * @test
     */
    public function it_handles_invisible_label_removed(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';
        $label = new Label('labelName', false);

        $this->mockGet($organizerId, 'organizer_with_one_label_invisible.json');

        $labelRemoved = new LabelRemoved($organizerId, $label);
        $domainMessage = $this->createDomainMessage($labelRemoved);

        $this->expectSave($organizerId, 'organizer_with_modified.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_geo_coordinates_updated(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer.json');

        $coordinates = new Coordinates(
            new Latitude(50.8795943),
            new Longitude(4.7150515)
        );
        $geoCoordinatesUpdated = new GeoCoordinatesUpdated(
            $organizerId,
            $coordinates
        );
        $domainMessage = $this->createDomainMessage($geoCoordinatesUpdated);

        $this->expectSave($organizerId, 'organizer_with_geo_coordinates.json');

        $this->projector->handle($domainMessage);
    }

    private function mockGet(string $organizerId, string $fileName): void
    {
        $organizerJson = file_get_contents(__DIR__ . '/Samples/' . $fileName);
        $this->documentRepository->method('fetch')
            ->with($organizerId)
            ->willReturn(new JsonDocument($organizerId, $organizerJson));
    }

    private function expectSave(string $organizerId, string $fileName): void
    {
        $expectedOrganizerJson = file_get_contents(__DIR__ . '/Samples/' . $fileName);
        // The expected organizer json still has newline formatting.
        // The actual organizer json on the other hand has no newlines
        // because it was created by using the withBody method on JsonDocument.
        // By calling json_encode(json_decode(...)) the newlines are also removed
        // from the expected document.
        $expectedOrganizerJson = json_encode(json_decode($expectedOrganizerJson));
        $this->documentRepository->expects($this->once())
            ->method('save')
            ->with(new JsonDocument($organizerId, $expectedOrganizerJson));
    }

    /**
     * @param ActorEvent|OrganizerEvent $organizerEvent
     */
    private function createDomainMessage($organizerEvent): DomainMessage
    {
        if ($organizerEvent instanceof ActorEvent) {
            $id = $organizerEvent->getActorId();
        } else {
            $id = $organizerEvent->getOrganizerId();
        }

        return new DomainMessage(
            $id,
            0,
            new Metadata(),
            $organizerEvent,
            $this->recordedOn->toBroadwayDateTime()
        );
    }
}
