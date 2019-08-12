<?php

namespace CultuurNet\UDB3\Model\Import\Place;

use Broadway\CommandHandling\Testing\TraceableCommandBus;
use Broadway\Repository\AggregateNotFoundException;
use Broadway\Repository\RepositoryInterface;
use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\ApiGuard\Consumer\ConsumerInterface;
use CultuurNet\UDB3\ApiGuard\Consumer\Specification\ConsumerSpecificationInterface;
use CultuurNet\UDB3\BookingInfo;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\ImageCollection;
use CultuurNet\UDB3\Media\Properties\CopyrightHolder;
use CultuurNet\UDB3\Media\Properties\Description as ImageDescription;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\Import\MediaObject\ImageCollectionFactory;
use CultuurNet\UDB3\Model\Import\Taxonomy\Label\LockedLabelRepository;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID as Udb3ModelUUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder as Udb3ModelCopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReference;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\MediaObjectReferences;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Labels;
use CultuurNet\UDB3\Model\ValueObject\Text\Description as Udb3ModelDescription;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language as Udb3ModelLanguage;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Place\Commands\DeleteCurrentOrganizer;
use CultuurNet\UDB3\Place\Commands\DeleteTypicalAgeRange;
use CultuurNet\UDB3\Place\Commands\ImportImages;
use CultuurNet\UDB3\Place\Commands\ImportLabels;
use CultuurNet\UDB3\Place\Commands\UpdateBookingInfo;
use CultuurNet\UDB3\Place\Commands\UpdateCalendar;
use CultuurNet\UDB3\Place\Commands\UpdateContactPoint;
use CultuurNet\UDB3\Place\Commands\UpdateDescription;
use CultuurNet\UDB3\Place\Commands\UpdateOrganizer;
use CultuurNet\UDB3\Place\Commands\UpdatePriceInfo;
use CultuurNet\UDB3\Place\Commands\UpdateTitle;
use CultuurNet\UDB3\Place\Commands\UpdateType;
use CultuurNet\UDB3\Place\Commands\UpdateTypicalAgeRange;
use CultuurNet\UDB3\Place\Place;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Model\Import\DecodedDocument;
use CultuurNet\UDB3\Model\Import\DocumentImporterInterface;
use CultuurNet\UDB3\Model\Import\PreProcessing\TermPreProcessingDocumentImporter;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceDenormalizer;
use CultuurNet\UDB3\Place\Commands\UpdateAddress;
use CultuurNet\UDB3\PriceInfo\BasePrice;
use CultuurNet\UDB3\PriceInfo\Price;
use CultuurNet\UDB3\PriceInfo\PriceInfo;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;
use ValueObjects\Geography\CountryCode;
use ValueObjects\Identity\UUID;
use ValueObjects\Money\Currency;
use ValueObjects\Person\Age;
use ValueObjects\Web\Url;

class PlaceDocumentImporterTest extends TestCase
{
    /**
     * @var RepositoryInterface|MockObject
     */
    private $repository;

    /**
     * @var PlaceDenormalizer
     */
    private $denormalizer;

    /**
     * @var ImageCollectionFactory|MockObject
     */
    private $imageCollectionFactory;

    /**
     * @var TraceableCommandBus
     */
    private $commandBus;

    /**
     * @var ConsumerInterface|MockObject
     */
    private $consumer;

    /**
     * @var ConsumerSpecificationInterface|MockObject
     */
    private $shouldApprove;

    /**
     * @var LockedLabelRepository|MockObject
     */
    private $lockedLabelRepository;

    /**
     * @var PlaceDocumentImporter
     */
    private $placeDocumentImporter;

    /**
     * @var TermPreProcessingDocumentImporter
     */
    private $termPreProcessingImporter;

    /**
     * @var DocumentImporterInterface
     */
    private $importer;

    public function setUp()
    {
        $this->repository = $this->createMock(RepositoryInterface::class);
        $this->denormalizer = new PlaceDenormalizer();
        $this->imageCollectionFactory = $this->createMock(ImageCollectionFactory::class);
        $this->commandBus = new TraceableCommandBus();
        $this->consumer = $this->createMock(ConsumerInterface::class);
        $this->shouldApprove = $this->createMock(ConsumerSpecificationInterface::class);
        $this->lockedLabelRepository = $this->createMock(LockedLabelRepository::class);

        $this->placeDocumentImporter = new PlaceDocumentImporter(
            $this->repository,
            $this->denormalizer,
            $this->imageCollectionFactory,
            $this->commandBus,
            $this->shouldApprove,
            $this->lockedLabelRepository
        );

        $this->termPreProcessingImporter = new TermPreProcessingDocumentImporter(
            $this->placeDocumentImporter,
            new PlaceLegacyBridgeCategoryResolver()
        );

        $this->importer = $this->termPreProcessingImporter;
    }

    /**
     * @test
     */
    public function it_should_create_a_new_place_and_publish_it_if_no_aggregate_exists_for_the_given_id()
    {
        $document = $this->getPlaceDocument();
        $id = $document->getId();

        $place = Place::createPlace(
            $id,
            new Language('nl'),
            new Title('Voorbeeld naam'),
            new EventType('0.14.0.0.0', 'Monument'),
            new Address(
                new Street('Henegouwenkaai 41-43'),
                new PostalCode('1080'),
                new Locality('Brussel'),
                new Country(CountryCode::fromNative('BE'))
            ),
            new Calendar(CalendarType::PERMANENT()),
            null
        );
        $place->publish(\DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T00:00:00+01:00'));

        $this->expectPlaceDoesNotExist($id);
        $this->expectCreatePlace($place);
        $this->expectNoImages();
        $this->expectNoLockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $expectedCommands = [
            new UpdateBookingInfo($id, new BookingInfo()),
            new UpdateContactPoint($id, new ContactPoint()),
            new DeleteCurrentOrganizer($id),
            new DeleteTypicalAgeRange($id),
            new UpdateTitle($id, new Language('fr'), new Title('Nom example')),
            new UpdateTitle($id, new Language('en'), new Title('Example name')),
            new UpdateAddress(
                $id,
                new Address(
                    new Street('Quai du Hainaut 41-43'),
                    new PostalCode('1080'),
                    new Locality('Bruxelles'),
                    new Country(CountryCode::fromNative('BE'))
                ),
                new Language('fr')
            ),
            new UpdateAddress(
                $id,
                new Address(
                    new Street('Henegouwenkaai 41-43'),
                    new PostalCode('1080'),
                    new Locality('Brussels'),
                    new Country(CountryCode::fromNative('BE'))
                ),
                new Language('en')
            ),
            new ImportLabels($id, new Labels()),
            new ImportImages($id, new ImageCollection()),
        ];

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals($expectedCommands, $recordedCommands);
    }

    /**
     * @test
     */
    public function it_should_create_a_new_place_and_approve_it_if_no_aggregate_exists_and_the_consumer_can_approve()
    {
        $document = $this->getPlaceDocument();
        $id = $document->getId();

        $this->shouldApprove->expects($this->once())
            ->method('satisfiedBy')
            ->with($this->consumer)
            ->willReturn(true);

        $place = Place::createPlace(
            $id,
            new Language('nl'),
            new Title('Voorbeeld naam'),
            new EventType('0.14.0.0.0', 'Monument'),
            new Address(
                new Street('Henegouwenkaai 41-43'),
                new PostalCode('1080'),
                new Locality('Brussel'),
                new Country(CountryCode::fromNative('BE'))
            ),
            new Calendar(CalendarType::PERMANENT()),
            null
        );
        $place->publish(\DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T00:00:00+01:00'));
        $place->approve();

        $this->expectPlaceDoesNotExist($id);
        $this->expectCreatePlace($place);
        $this->expectNoImages();
        $this->expectNoLockedLabels();

        $this->commandBus->record();

        $this->importer->import($document, $this->consumer);

        $expectedCommands = [
            new UpdateBookingInfo($id, new BookingInfo()),
            new UpdateContactPoint($id, new ContactPoint()),
            new DeleteCurrentOrganizer($id),
            new DeleteTypicalAgeRange($id),
            new UpdateTitle($id, new Language('fr'), new Title('Nom example')),
            new UpdateTitle($id, new Language('en'), new Title('Example name')),
            new UpdateAddress(
                $id,
                new Address(
                    new Street('Quai du Hainaut 41-43'),
                    new PostalCode('1080'),
                    new Locality('Bruxelles'),
                    new Country(CountryCode::fromNative('BE'))
                ),
                new Language('fr')
            ),
            new UpdateAddress(
                $id,
                new Address(
                    new Street('Henegouwenkaai 41-43'),
                    new PostalCode('1080'),
                    new Locality('Brussels'),
                    new Country(CountryCode::fromNative('BE'))
                ),
                new Language('en')
            ),
            new ImportLabels($id, new Labels()),
            new ImportImages($id, new ImageCollection()),
        ];

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals($expectedCommands, $recordedCommands);
    }

    /**
     * @test
     */
    public function it_should_update_an_existing_place_if_an_aggregate_exists_for_the_given_id()
    {
        $document = $this->getPlaceDocument();
        $id = $document->getId();

        $this->expectPlaceExists($id);
        $this->expectNoImages();
        $this->expectNoLockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $expectedCommands = [
            new UpdateTitle($id, new Language('nl'), new Title('Voorbeeld naam')),
            new UpdateType($id, new EventType('0.14.0.0.0', 'Monument')),
            new UpdateAddress(
                $id,
                new Address(
                    new Street('Henegouwenkaai 41-43'),
                    new PostalCode('1080'),
                    new Locality('Brussel'),
                    new Country(CountryCode::fromNative('BE'))
                ),
                new Language('nl')
            ),
            new UpdateCalendar($id, new Calendar(CalendarType::PERMANENT())),
            new UpdateBookingInfo($id, new BookingInfo()),
            new UpdateContactPoint($id, new ContactPoint()),
            new DeleteCurrentOrganizer($id),
            new DeleteTypicalAgeRange($id),
            new UpdateTitle($id, new Language('fr'), new Title('Nom example')),
            new UpdateTitle($id, new Language('en'), new Title('Example name')),
            new UpdateAddress(
                $id,
                new Address(
                    new Street('Quai du Hainaut 41-43'),
                    new PostalCode('1080'),
                    new Locality('Bruxelles'),
                    new Country(CountryCode::fromNative('BE'))
                ),
                new Language('fr')
            ),
            new UpdateAddress(
                $id,
                new Address(
                    new Street('Henegouwenkaai 41-43'),
                    new PostalCode('1080'),
                    new Locality('Brussels'),
                    new Country(CountryCode::fromNative('BE'))
                ),
                new Language('en')
            ),
            new ImportLabels($id, new Labels()),
            new ImportImages($id, new ImageCollection()),
        ];

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertEquals($expectedCommands, $recordedCommands);
    }

    /**
     * @test
     */
    public function it_should_update_the_description_and_translations()
    {
        $document = $this->getPlaceDocument();
        $body = $document->getBody();
        $body['description'] = [
            'nl' => 'Voorbeeld beschrijving',
            'en' => 'Example description',
        ];
        $document = $document->withBody($body);
        $id = $document->getId();

        $this->expectPlaceExists($id);
        $this->expectNoImages();
        $this->expectNoLockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            new UpdateDescription($id, new Language('nl'), new Description('Voorbeeld beschrijving')),
            $recordedCommands
        );

        $this->assertContainsObject(
            new UpdateDescription($id, new Language('en'), new Description('Example description')),
            $recordedCommands
        );
    }

    /**
     * @test
     */
    public function it_should_update_the_organizer_id()
    {
        $document = $this->getPlaceDocument();
        $body = $document->getBody();
        $body['organizer'] = [
            '@id' => 'http://io.uitdatabank.be/organizers/a106a4cb-5c5f-496b-97e0-4d63b9e09260',
        ];
        $document = $document->withBody($body);
        $id = $document->getId();

        $this->expectPlaceExists($id);
        $this->expectNoImages();
        $this->expectNoLockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            new UpdateOrganizer($id, 'a106a4cb-5c5f-496b-97e0-4d63b9e09260'),
            $recordedCommands
        );
    }

    /**
     * @test
     */
    public function it_should_update_the_typical_age_range()
    {
        $document = $this->getPlaceDocument();
        $body = $document->getBody();
        $body['typicalAgeRange'] = '8-12';
        $document = $document->withBody($body);
        $id = $document->getId();

        $this->expectPlaceExists($id);
        $this->expectNoImages();
        $this->expectNoLockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            new UpdateTypicalAgeRange($id, new AgeRange(new Age(8), new Age(12))),
            $recordedCommands
        );
    }

    /**
     * @test
     */
    public function it_should_update_the_price_info()
    {
        $document = $this->getPlaceDocument();
        $body = $document->getBody();
        $body['priceInfo'] = [
            [
                'category' => 'base',
                'name' => ['nl' => 'Basistarief'],
                'price' => 10,
                'priceCurrency' => 'EUR',
            ],
        ];
        $document = $document->withBody($body);
        $id = $document->getId();

        $this->expectPlaceExists($id);
        $this->expectNoImages();
        $this->expectNoLockedLabels();

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            new UpdatePriceInfo(
                $id,
                new PriceInfo(
                    new BasePrice(
                        new Price(1000),
                        Currency::fromNative('EUR')
                    )
                )
            ),
            $recordedCommands
        );
    }

    /**
     * @test
     */
    public function it_should_import_media_object_references()
    {
        $document = $this->getPlaceDocument();
        $body = $document->getBody();
        $body['mediaObject'] = [
            [
                '@id' => 'https://io.uitdatabank.be/images/6984df33-62b4-4c94-ba2d-59d4a87d17dd.png',
                'description' => 'Example description',
                'copyrightHolder' => 'Bob',
                'inLanguage' => 'en',
            ],
            [
                '@id' => 'https://io.uitdatabank.be/images/ff29632f-c277-4e27-bb97-3fdb14e90279.png',
                'description' => 'Voorbeeld beschrijving',
                'copyrightHolder' => 'Bob',
                'inLanguage' => 'nl',
            ],
        ];
        $document = $document->withBody($body);
        $id = $document->getId();

        $this->expectPlaceExists($id);
        $this->expectNoLockedLabels();

        $expectedImages = ImageCollection::fromArray(
            [
                new Image(
                    new UUID('6984df33-62b4-4c94-ba2d-59d4a87d17dd'),
                    MIMEType::fromSubtype('png'),
                    new ImageDescription('Example description'),
                    new CopyrightHolder('Bob'),
                    Url::fromNative('https://io.uitdatabank.be/images/6984df33-62b4-4c94-ba2d-59d4a87d17dd.png'),
                    new Language('en')
                ),
                new Image(
                    new UUID('ff29632f-c277-4e27-bb97-3fdb14e90279'),
                    MIMEType::fromSubtype('png'),
                    new ImageDescription('Voorbeeld beschrijving'),
                    new CopyrightHolder('Bob'),
                    Url::fromNative('https://io.uitdatabank.be/images/ff29632f-c277-4e27-bb97-3fdb14e90279.png'),
                    new Language('nl')
                ),
            ]
        );

        $this->imageCollectionFactory->expects($this->once())
            ->method('fromMediaObjectReferences')
            ->with(
                new MediaObjectReferences(
                    MediaObjectReference::createWithMediaObjectId(
                        new Udb3ModelUUID('6984df33-62b4-4c94-ba2d-59d4a87d17dd'),
                        new Udb3ModelDescription('Example description'),
                        new Udb3ModelCopyrightHolder('Bob'),
                        new Udb3ModelLanguage('en')
                    ),
                    MediaObjectReference::createWithMediaObjectId(
                        new Udb3ModelUUID('ff29632f-c277-4e27-bb97-3fdb14e90279'),
                        new Udb3ModelDescription('Voorbeeld beschrijving'),
                        new Udb3ModelCopyrightHolder('Bob'),
                        new Udb3ModelLanguage('nl')
                    )
                )
            )
            ->willReturn($expectedImages);

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            new ImportImages($id, $expectedImages),
            $recordedCommands
        );
    }

    /**
     * @test
     */
    public function it_should_update_an_existing_place_with_labels()
    {
        $document = $this->getPlaceDocument();
        $body = $document->getBody();
        $body['labels'] = [
            'foo',
            'bar',
        ];
        $body['hiddenLabels'] = [
            'lorem',
            'ipsum',
        ];
        $document = $document->withBody($body);
        $id = $document->getId();

        $this->expectPlaceExists($id);
        $this->expectNoImages();

        $lockedLabels = new Labels(
            new Label(new LabelName('locked1')),
            new Label(new LabelName('locked2'))
        );
        $this->lockedLabelRepository->expects($this->once())
            ->method('getLockedLabelsForItem')
            ->with($id)
            ->willReturn($lockedLabels);

        $this->commandBus->record();

        $this->importer->import($document);

        $recordedCommands = $this->commandBus->getRecordedCommands();

        $this->assertContainsObject(
            (
                new ImportLabels(
                    $this->getPlaceId(),
                    new Labels(
                        new Label(new LabelName('foo'), true),
                        new Label(new LabelName('bar'), true),
                        new Label(new LabelName('lorem'), false),
                        new Label(new LabelName('ipsum'), false)
                    )
                )
            )->withLabelsToKeepIfAlreadyOnOffer($lockedLabels),
            $recordedCommands
        );
    }

    /**
     * @return string
     */
    private function getPlaceId()
    {
        return 'f3277646-1cc8-4af9-b6d5-a47f3c4f2ac0';
    }

    /**
     * @return array
     */
    private function getPlaceData()
    {
        return [
            '@id' => 'https://io.uitdatabank.be/places/f3277646-1cc8-4af9-b6d5-a47f3c4f2ac0',
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'Voorbeeld naam',
                'fr' => 'Nom example',
                'en' => 'Example name',
            ],
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.14.0.0.0',
                    'label' => 'Monument',
                    'domain' => 'eventtype',
                ],
            ],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussel',
                    'addressCountry' => 'BE',
                ],
                'fr' => [
                    'streetAddress' => 'Quai du Hainaut 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Bruxelles',
                    'addressCountry' => 'BE',
                ],
                'en' => [
                    'streetAddress' => 'Henegouwenkaai 41-43',
                    'postalCode' => '1080',
                    'addressLocality' => 'Brussels',
                    'addressCountry' => 'BE',
                ],
            ],
            'availableFrom' => '2018-01-01T00:00:00+01:00',
        ];
    }

    /**
     * @return DecodedDocument
     */
    private function getPlaceDocument()
    {
        return new DecodedDocument($this->getPlaceId(), $this->getPlaceData());
    }

    /**
     * @param string $placeId
     */
    private function expectPlaceExists($placeId)
    {
        $this->repository->expects($this->once())
            ->method('load')
            ->with($placeId)
            ->willReturn($this->createMock(Place::class));
    }

    private function expectPlaceDoesNotExist($placeId)
    {
        $this->repository->expects($this->once())
            ->method('load')
            ->with($placeId)
            ->willThrowException(new AggregateNotFoundException());
    }

    private function expectCreatePlace(Place $expectedPlace)
    {
        $this->repository->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Place $place) use ($expectedPlace) {
                return $expectedPlace->getAggregateRootId() === $place->getAggregateRootId();
            }));
    }

    private function expectNoImages()
    {
        $this->imageCollectionFactory->expects($this->any())
            ->method('fromMediaObjectReferences')
            ->willReturn(new ImageCollection());
    }

    private function expectNoLockedLabels()
    {
        $this->lockedLabelRepository->expects($this->any())
            ->method('getLockedLabelsForItem')
            ->willReturn(new Labels());
    }

    private function assertContainsObject($needle, array $haystack)
    {
        $this->assertContains(
            $needle,
            $haystack,
            '',
            false,
            false
        );
    }
}
