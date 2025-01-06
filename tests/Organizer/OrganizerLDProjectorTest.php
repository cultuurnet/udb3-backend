<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\Repository\Repository;
use Broadway\Serializer\Serializable;
use Broadway\UuidGenerator\Rfc4122\Version4Generator;
use CultuurNet\UDB3\Actor\ActorEvent;
use CultuurNet\UDB3\Address\CultureFeed\CultureFeedAddressFactory;
use CultuurNet\UDB3\Cdb\CdbXMLToJsonLDLabelImporter;
use CultuurNet\UDB3\Completeness\CompletenessFromWeights;
use CultuurNet\UDB3\Completeness\Weights;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Label\ReadModels\JSON\Repository\ReadRepositoryInterface;
use CultuurNet\UDB3\Media\MediaObject;
use CultuurNet\UDB3\Media\Properties\Description;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\Serializer\ValueObject\MediaObject\ImageNormalizer;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Organizer\Events\AddressRemoved;
use CultuurNet\UDB3\Organizer\Events\AddressTranslated;
use CultuurNet\UDB3\Organizer\Events\AddressUpdated;
use CultuurNet\UDB3\Organizer\Events\ContactPointUpdated;
use CultuurNet\UDB3\Organizer\Events\DescriptionDeleted;
use CultuurNet\UDB3\Organizer\Events\DescriptionUpdated;
use CultuurNet\UDB3\Organizer\Events\EducationalDescriptionDeleted;
use CultuurNet\UDB3\Organizer\Events\EducationalDescriptionUpdated;
use CultuurNet\UDB3\Organizer\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Organizer\Events\ImageAdded;
use CultuurNet\UDB3\Organizer\Events\ImageRemoved;
use CultuurNet\UDB3\Organizer\Events\ImageUpdated;
use CultuurNet\UDB3\Organizer\Events\LabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved;
use CultuurNet\UDB3\Organizer\Events\MainImageUpdated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreated;
use CultuurNet\UDB3\Organizer\Events\OrganizerCreatedWithUniqueWebsite;
use CultuurNet\UDB3\Organizer\Events\OrganizerDeleted;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OrganizerUpdatedFromUDB2;
use CultuurNet\UDB3\Organizer\Events\OwnerChanged;
use CultuurNet\UDB3\Organizer\Events\TitleTranslated;
use CultuurNet\UDB3\Organizer\Events\TitleUpdated;
use CultuurNet\UDB3\Organizer\Events\WebsiteUpdated;
use CultuurNet\UDB3\Organizer\ReadModel\JSONLD\CdbXMLImporter;
use CultuurNet\UDB3\Organizer\ReadModel\JSONLD\OrganizerJsonDocumentLanguageAnalyzer;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ReadModel\JsonDocumentLanguageEnricher;
use CultuurNet\UDB3\RecordedOn;
use CultuurNet\UDB3\SampleFiles;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrganizerLDProjectorTest extends TestCase
{
    private OrganizerLDProjector $projector;

    /**
     * @var DocumentRepository&MockObject
     */
    private $documentRepository;

    /**
     * @var Repository&MockObject
     */
    private $imageRepository;

    private RecordedOn $recordedOn;

    public function setUp(): void
    {
        $this->documentRepository = $this->createMock(DocumentRepository::class);

        $iriGenerator = new CallableIriGenerator(
            function ($id) {
                return 'http://example.com/entity/' . $id;
            }
        );

        $this->imageRepository = $this->createMock(Repository::class);

        $this->projector = new OrganizerLDProjector(
            $this->documentRepository,
            $iriGenerator,
            new JsonDocumentLanguageEnricher(
                new OrganizerJsonDocumentLanguageAnalyzer()
            ),
            new ImageNormalizer(
                $this->imageRepository,
                $iriGenerator
            ),
            new CdbXMLImporter(
                new CdbXMLToJsonLDLabelImporter($this->createMock(ReadRepositoryInterface::class)),
                new CultureFeedAddressFactory()
            ),
            new CompletenessFromWeights(
                Weights::fromConfig([
                    'name' => 20,
                    'url' => 20,
                    'contactPoint' => 20,
                    'description' => 15,
                    'images' => 15,
                    'address' => 10,
                ])
            ),
        );

        $this->recordedOn = RecordedOn::fromBroadwayDateTime(
            DateTime::fromString('2018-01-18T13:57:09Z')
        );
    }

    private function organizerImportedFromUDB2(string $fileName): OrganizerImportedFromUDB2
    {
        $cdbXml = SampleFiles::read(
            __DIR__ . '/Samples/' . $fileName
        );

        return new OrganizerImportedFromUDB2(
            'someId',
            $cdbXml,
            'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.2/FINAL'
        );
    }

    private function organizerUpdatedFromUDB2(string $fileName): OrganizerUpdatedFromUDB2
    {
        $cdbXml = SampleFiles::read(
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
        $country = new CountryCode('BE');

        $organizerCreated = new OrganizerCreated(
            $id,
            'some representative title',
            'Kerkstraat 69',
            '3000',
            'Leuven',
            'BE',
            ['050/123'],
            ['test@test.be', 'test2@test.be'],
            ['http://www.google.be'],
        );

        $jsonLD = new \stdClass();
        $jsonLD->{'@id'} = 'http://example.com/entity/' . $id;
        $jsonLD->{'@context'} = '/contexts/organizer';

        $jsonLD->mainLanguage = 'nl';
        $jsonLD->name[$jsonLD->mainLanguage] = 'some representative title';

        $jsonLD->address = [
            'nl' => [
                'addressCountry' => $country->toString(),
                'addressLocality' => $locality->toString(),
                'postalCode' => $postalCode->toString(),
                'streetAddress' => $street->toString(),
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
        $jsonLD->completeness = 50;

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
            'en',
            'http://www.stuk.be',
            'some representative title'
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
        $jsonLD->completeness = 40;

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
    public function it_handles_owner_changed(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer.json');

        $domainMessage = $this->createDomainMessage(
            new OwnerChanged($organizerId, '9906d685-9557-4422-a3a9-44aec6e2a23f')
        );

        $this->expectSave($organizerId, 'organizer_with_changed_owner.json');

        $this->projector->handle($domainMessage);
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

    /**
     * @test
     */
    public function it_handles_description_updated(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer.json');

        $domainMessage = $this->createDomainMessage(
            new DescriptionUpdated(
                $organizerId,
                'Description of the organizer',
                'en'
            )
        );

        $this->expectSave($organizerId, 'organizer_with_updated_description.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_long_description_updated(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer.json');

        $domainMessage = $this->createDomainMessage(
            new DescriptionUpdated(
                $organizerId,
                'This is a very long description of the organizer, it has more then 200 characters and because of that the description is taken into account for the completeness of the organizer. That makes this string difficult to read and understand.',
                'en'
            )
        );

        $this->expectSave($organizerId, 'organizer_with_long_updated_description.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_description_translated(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer_with_updated_description.json');

        $domainMessage = $this->createDomainMessage(
            new DescriptionUpdated(
                $organizerId,
                'Description de l\'organisation',
                'fr'
            )
        );

        $this->expectSave($organizerId, 'organizer_with_translated_description.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_deleting_a_translated_description(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer_with_translated_description.json');

        $domainMessage = $this->createDomainMessage(
            new DescriptionDeleted($organizerId, 'fr')
        );

        $this->expectSave($organizerId, 'organizer_with_updated_description.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_deleting_a_description(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer_with_updated_description.json');

        $domainMessage = $this->createDomainMessage(
            new DescriptionDeleted($organizerId, 'en')
        );

        $this->expectSave($organizerId, 'organizer_without_description.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_educational_description_updated(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer.json');

        $domainMessage = $this->createDomainMessage(
            new EducationalDescriptionUpdated(
                $organizerId,
                'Educational description of the organizer',
                'en'
            )
        );

        $this->expectSave($organizerId, 'organizer_with_updated_educational_description.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_educational_description_translated(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer_with_updated_educational_description.json');

        $domainMessage = $this->createDomainMessage(
            new EducationalDescriptionUpdated(
                $organizerId,
                'Description educatif de l\'organisation',
                'fr'
            )
        );

        $this->expectSave($organizerId, 'organizer_with_translated_educational_description.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_deleting_a_translated_educational_description(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer_with_translated_educational_description.json');

        $domainMessage = $this->createDomainMessage(
            new EducationalDescriptionDeleted($organizerId, 'fr')
        );

        $this->expectSave($organizerId, 'organizer_with_updated_educational_description.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_deleting_an_educational_description(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer_with_updated_educational_description.json');

        $domainMessage = $this->createDomainMessage(
            new EducationalDescriptionDeleted($organizerId, 'en')
        );

        $this->expectSave($organizerId, 'organizer_without_description.json');

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
    public function it_handles_address_updated(string $currentJson, string $expectedJson): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, $currentJson);

        $domainMessage = $this->createDomainMessage(
            new AddressUpdated(
                $organizerId,
                'Martelarenplein',
                '3000',
                'Leuven',
                'BE'
            )
        );

        $this->expectSave($organizerId, $expectedJson);

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_contact_point_updated(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer.json');

        $domainMessage = $this->createDomainMessage(
            new ContactPointUpdated(
                $organizerId,
                ['02/551 18 70'],
                ['info@publiq.be'],
                ['https://www.publiq.be']
            )
        );

        $this->expectSave($organizerId, 'organizer_with_updated_contact_point.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_contact_point_updated_with_empty_values(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer.json');

        $domainMessage = $this->createDomainMessage(
            new ContactPointUpdated(
                $organizerId,
                [],
                [],
                []
            )
        );

        $this->expectSave($organizerId, 'organizer_with_empty_contact_point.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_adding_an_initial_image(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';
        $this->mockGet($organizerId, 'organizer.json');

        $this->imageRepository->method('load')
            ->with('03789a2f-5063-4062-b7cb-95a0a2280d92')
            ->willReturn(
                MediaObject::create(
                    new Uuid('03789a2f-5063-4062-b7cb-95a0a2280d92'),
                    MIMEType::fromSubtype('jpeg'),
                    new Description('Uploaded image'),
                    new CopyrightHolder('publiq'),
                    new Url('https://images.uitdatabank.be/03789a2f-5063-4062-b7cb-95a0a2280d92.jpg'),
                    new Language('nl')
                )
            );

        $domainMessage = $this->createDomainMessage(
            new ImageAdded(
                $organizerId,
                '03789a2f-5063-4062-b7cb-95a0a2280d92',
                'nl',
                'Beschrijving van de afbeelding',
                'publiq'
            )
        );

        $this->expectSave($organizerId, 'organizer_with_one_image.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_adding_an_extra_image(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';
        $this->mockGet($organizerId, 'organizer_with_one_image.json');

        $this->imageRepository->method('load')
            ->with('dd45e5a1-f70c-48d7-83e5-dde9226c1dd6')
            ->willReturn(
                MediaObject::create(
                    new Uuid('dd45e5a1-f70c-48d7-83e5-dde9226c1dd6'),
                    MIMEType::fromSubtype('png'),
                    new Description('Extra image'),
                    new CopyrightHolder('madewithlove'),
                    new Url('https://images.uitdatabank.be/dd45e5a1-f70c-48d7-83e5-dde9226c1dd6.png'),
                    new Language('en')
                )
            );

        $domainMessage = $this->createDomainMessage(
            new ImageAdded(
                $organizerId,
                'dd45e5a1-f70c-48d7-83e5-dde9226c1dd6',
                'en',
                'Extra image',
                'madewithlove'
            )
        );

        $this->expectSave($organizerId, 'organizer_with_two_images.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_updating_an_image(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';
        $this->mockGet($organizerId, 'organizer_with_one_image.json');

        $this->imageRepository->method('load')
            ->with('03789a2f-5063-4062-b7cb-95a0a2280d92')
            ->willReturn(
                MediaObject::create(
                    new Uuid('03789a2f-5063-4062-b7cb-95a0a2280d92'),
                    MIMEType::fromSubtype('jpeg'),
                    new Description('Image Description'),
                    new CopyrightHolder('madewithlove'),
                    new Url('https://images.uitdatabank.be/03789a2f-5063-4062-b7cb-95a0a2280d92.jpg'),
                    new Language('en')
                )
            );

        $domainMessage = $this->createDomainMessage(
            new ImageUpdated(
                $organizerId,
                '03789a2f-5063-4062-b7cb-95a0a2280d92',
                'en',
                'Updated description',
                'Updated copyright holder'
            )
        );

        $this->expectSave($organizerId, 'organizer_with_updated_image.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_removing_an_image(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';
        $this->mockGet($organizerId, 'organizer_with_two_images.json');

        $domainMessage = $this->createDomainMessage(
            new ImageRemoved(
                $organizerId,
                'dd45e5a1-f70c-48d7-83e5-dde9226c1dd6',
            )
        );

        $this->expectSave($organizerId, 'organizer_with_one_image.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_removing_main_image(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';
        $this->mockGet($organizerId, 'organizer_with_two_images.json');

        $domainMessage = $this->createDomainMessage(
            new MainImageUpdated(
                $organizerId,
                'dd45e5a1-f70c-48d7-83e5-dde9226c1dd6',
            )
        );
        $this->projector->handle($domainMessage);

        $domainMessage = $this->createDomainMessage(
            new ImageRemoved(
                $organizerId,
                'dd45e5a1-f70c-48d7-83e5-dde9226c1dd6',
            )
        );

        $this->expectSave($organizerId, 'organizer_with_one_image.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_removing_main_image_with_different_urls(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';
        $this->mockGet($organizerId, 'organizer_with_two_images_with_different_urls.json');

        $domainMessage = $this->createDomainMessage(
            new ImageRemoved(
                $organizerId,
                'dd45e5a1-f70c-48d7-83e5-dde9226c1dd6',
            )
        );

        $this->expectSave($organizerId, 'organizer_with_one_image_with_different_urls.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_removing_last_image(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';
        $this->mockGet($organizerId, 'organizer_with_one_image.json');

        $domainMessage = $this->createDomainMessage(
            new ImageRemoved(
                $organizerId,
                '03789a2f-5063-4062-b7cb-95a0a2280d92',
            )
        );

        $this->expectSave($organizerId, 'organizer_with_all_images_removed.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_setting_a_main_image(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';
        $this->mockGet($organizerId, 'organizer_with_two_images.json');

        $domainMessage = $this->createDomainMessage(
            new MainImageUpdated(
                $organizerId,
                'dd45e5a1-f70c-48d7-83e5-dde9226c1dd6',
            )
        );

        $this->expectSave($organizerId, 'organizer_with_two_images_and_updated_main_image.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     */
    public function it_handles_address_removed(): void
    {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, 'organizer_with_address.json');

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

        $organizerJson = SampleFiles::read(__DIR__ . '/Samples/organizer_with_main_language.json');
        $organizerJson = Json::decode($organizerJson);
        $organizerJson->name->en = 'English name';
        $organizerJson = Json::encode($organizerJson);

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
                'Rue',
                '3010',
                'Kessel-Lo (Louvain)',
                'BE',
                'fr'
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

        $this->mockGet($organizerId, 'organizer.json');

        $labelAdded = new LabelAdded($organizerId, 'labelName', true);
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

        $this->mockGet($organizerId, 'organizer.json');

        $labelAdded = new LabelAdded($organizerId, 'labelName', false);
        $domainMessage = $this->createDomainMessage($labelAdded);

        $this->expectSave($organizerId, 'organizer_with_one_label_invisible.json');

        $this->projector->handle($domainMessage);
    }

    /**
     * @test
     * @dataProvider labelRemovedDataProvider
     */
    public function it_handles_label_removed(
        string $labelName,
        string $originalFile,
        string $finalFile
    ): void {
        $organizerId = '586f596d-7e43-4ab9-b062-04db9436fca4';

        $this->mockGet($organizerId, $originalFile);

        $labelRemoved = new LabelRemoved($organizerId, $labelName);
        $domainMessage = $this->createDomainMessage($labelRemoved);

        $this->expectSave($organizerId, $finalFile);

        $this->projector->handle($domainMessage);
    }

    public function labelRemovedDataProvider(): array
    {
        return [
            [
                'labelName',
                'organizer_with_one_label.json',
                'organizer_with_modified.json',
            ],
            [
                'anotherLabel',
                'organizer_with_two_labels.json',
                'organizer_with_one_label.json',
            ],
            [
                'yetAnotherLabel',
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
        $label = new Label(new LabelName('labelName'), false);

        $this->mockGet($organizerId, 'organizer_with_one_label_invisible.json');

        $labelRemoved = new LabelRemoved($organizerId, $label->getName()->toString(), $label->isVisible());
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

        $geoCoordinatesUpdated = new GeoCoordinatesUpdated(
            $organizerId,
            50.8795943,
            4.7150515
        );
        $domainMessage = $this->createDomainMessage($geoCoordinatesUpdated);

        $this->expectSave($organizerId, 'organizer_with_geo_coordinates.json');

        $this->projector->handle($domainMessage);
    }

    private function mockGet(string $organizerId, string $fileName): void
    {
        $organizerJson = SampleFiles::read(__DIR__ . '/Samples/' . $fileName);
        $this->documentRepository->method('fetch')
            ->with($organizerId)
            ->willReturn(new JsonDocument($organizerId, $organizerJson));
    }

    private function expectSave(string $organizerId, string $fileName): void
    {
        $expectedOrganizerJson = SampleFiles::read(__DIR__ . '/Samples/' . $fileName);
        // The expected organizer json still has newline formatting.
        // The actual organizer json on the other hand has no newlines
        // because it was created by using the withBody method on JsonDocument.
        // By calling json_encode(json_decode(...)) the newlines are also removed
        // from the expected document.
        $expectedOrganizerJson = Json::encode(Json::decode($expectedOrganizerJson));
        $this->documentRepository->expects($this->once())
            ->method('save')
            ->with(new JsonDocument($organizerId, $expectedOrganizerJson));
    }

    /**
     * @param Serializable|ActorEvent $organizerEvent
     */
    private function createDomainMessage($organizerEvent): DomainMessage
    {
        if ($organizerEvent instanceof ActorEvent) {
            $id = $organizerEvent->getActorId();
        } else {
            $id = $organizerEvent->getOrganizerId(); //@phpstan-ignore-line
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
