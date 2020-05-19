<?php

namespace CultuurNet\UDB3;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListenerInterface;
use CultuurNet\UDB3\Event\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\Properties\CopyrightHolder;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ValueObjects\Identity\UUID;
use ValueObjects\Person\Age;
use ValueObjects\StringLiteral\StringLiteral;
use ValueObjects\Web\Url;

/**
 * Base test  case class for testing common Offer JSON-LD projector
 * functionality.
 */
abstract class OfferLDProjectorTestBase extends TestCase
{
    /**
     * @var InMemoryDocumentRepository
     */
    protected $documentRepository;

    /**
     * @var EventListenerInterface
     */
    protected $projector;

    /**
     * @var string
     */
    protected $eventNamespace;

    /**
     * @var RecordedOn
     */
    protected $recordedOn;

    /**
     * @var OrganizerService|MockObject
     */
    protected $organizerService;

    public function __construct($name, array $data, $dataName, $eventNamespace)
    {
        parent::__construct($name, $data, $dataName);

        $this->eventNamespace = $eventNamespace;

        $this->recordedOn = RecordedOn::fromBroadwayDateTime(DateTime::now());
    }

    /**
     * Get the namespaced classname of the event to create.
     * @param string $className
     *   Name of the class
     * @return string
     */
    private function getEventClass($className)
    {
        return $this->eventNamespace . '\\Events\\' . $className;
    }

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->documentRepository = new InMemoryDocumentRepository();

        $this->organizerService = $this->createMock(OrganizerService::class);
    }

    /**
     * @param object $event
     * @param string $entityId
     * @param Metadata|null $metadata
     * @param DateTime $dateTime
     * @param bool $returnBody
     * @return \stdClass
     */
    protected function project(
        $event,
        $entityId,
        Metadata $metadata = null,
        DateTime $dateTime = null,
        $returnBody = true
    ) {
        if (null === $metadata) {
            $metadata = new Metadata();
        }

        if (null === $dateTime) {
            $dateTime = DateTime::now();
        }

        $this->projector->handle(
            new DomainMessage(
                $entityId,
                1,
                $metadata,
                $event,
                $dateTime
            )
        );

        if ($returnBody) {
            return $this->getBody($entityId);
        }
    }

    /**
     * @param string $id
     * @return \stdClass
     */
    protected function getBody($id)
    {
        $document = $this->documentRepository->get($id);
        return $document->getBody();
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_booking_info()
    {
        $id = 'foo';
        $url = 'http://www.google.be';
        $urlLabel = new MultilingualString(new Language('nl'), new StringLiteral('Google'));
        $phone = '045';
        $email = 'test@test.com';
        $availabilityStarts = \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T00:00:00+01:00');
        $availabilityEnds = \DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-31T00:00:00+01:00');
        $bookingInfo = new BookingInfo($url, $urlLabel, $phone, $email, $availabilityStarts, $availabilityEnds);
        $eventClass = $this->getEventClass('BookingInfoUpdated');
        $bookingInfoUpdated = new $eventClass($id, $bookingInfo);

        $initialDocument = new JsonDocument($id);

        $this->documentRepository->save($initialDocument);

        $expectedBody = (object)[
            'bookingInfo' => (object)[
                'phone' => $phone,
                'email' => $email,
                'url' => $url,
                'urlLabel' => (object) $urlLabel->serialize(),
                'availabilityStarts' => '2018-01-01T00:00:00+01:00',
                'availabilityEnds' => '2018-01-31T00:00:00+01:00',
            ],
            'modified' => $this->recordedOn->toString(),
            'languages' => ['nl'],
        ];

        $body = $this->project($bookingInfoUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_contact_point()
    {
        $id = 'foo';
        $phones = ['045', '046'];
        $emails = ['test@test.be', 'test@test2.be'];
        $urls = ['http://www.google.be', 'http://www.google2.be'];
        $contactPoint = new ContactPoint($phones, $emails, $urls);
        $eventClass = $this->getEventClass('ContactPointUpdated');
        $contactPointUpdated = new $eventClass($id, $contactPoint);

        $initialDocument = new JsonDocument($id);
        $this->documentRepository->save($initialDocument);

        $body = $this->project($contactPointUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());

        $expectedBody = (object)[
            'contactPoint' => (object)[
                'phone' => $phones,
                'email' => $emails,
                'url' => $urls,
            ],
            'modified' => $this->recordedOn->toString(),
        ];

        $this->assertEquals(
            $expectedBody,
            $body
        );
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_description()
    {
        $description = new \Cultuurnet\UDB3\Description('description');
        $id = 'foo';
        $eventClass = $this->getEventClass('DescriptionUpdated');
        $descriptionUpdated = new $eventClass($id, $description);

        $initialDocument = new JsonDocument(
            $id,
            json_encode(
                [
                    'name' => [
                        'nl' => 'Foo',
                    ],
                ]
            )
        );

        $this->documentRepository->save($initialDocument);

        $expectedBody = (object) [
            'name' => (object) [
                'nl' => 'Foo',
            ],
            'description' => (object) [
                'nl' => $description,
            ],
            'languages' => ['nl'],
            'completedLanguages' => ['nl'],
            'modified' => $this->recordedOn->toString(),
        ];

        $body = $this->project($descriptionUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_adding_of_an_image()
    {
        $id = 'foo';
        $imageId = UUID::fromNative('de305d54-75b4-431b-adb2-eb6b9e546014');
        $description = new \Cultuurnet\UDB3\Media\Properties\Description('Some description.');
        $copyrightHolder = new CopyrightHolder('Dirk Dirkington');
        $type = new MIMEType('image/png');
        $location = Url::fromNative('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png');
        $language = new Language('en');

        $image = new Image($imageId, $type, $description, $copyrightHolder, $location, $language);
        $eventClass = $this->getEventClass('ImageAdded');
        $imageAdded = new $eventClass($id, $image);

        $initialDocument = new JsonDocument($id);
        $this->documentRepository->save($initialDocument);

        $expectedBody = (object)[
            'image' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
            'mediaObject' => [
                (object)[
                    '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                    '@type' => 'schema:ImageObject',
                    'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                    'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                    'description' => (string) $description,
                    'copyrightHolder' => (string) $copyrightHolder,
                    'inLanguage' => 'en',
                ],
            ],
            'modified' => $this->recordedOn->toString(),
        ];

        $body = $this->project($imageAdded, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_editing_of_an_image()
    {
        $id = 'foo';
        $imageId = UUID::fromNative('de305d54-75b4-431b-adb2-eb6b9e546014');
        $description = StringLiteral::fromNative('Some description.');
        $copyrightHolder = StringLiteral::fromNative('Dirk Dirkington');
        $eventClass = $this->getEventClass('ImageUpdated');
        $imageUpdated = new $eventClass($id, $imageId, $description, $copyrightHolder);

        $initialDocument = new JsonDocument(
            $id,
            json_encode([
                'mediaObject' => [
                    [
                        '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                        '@type' => 'schema:ImageObject',
                        'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                        'description' => 'olddescription',
                        'copyrightHolder' => 'oldcopyrightHolder',
                        'inLanguage' => 'en',
                    ],
                ],
            ])
        );
        $this->documentRepository->save($initialDocument);

        $expectedBody = (object)[
            'mediaObject' => [
                (object)[
                    '@id' => 'http://example.com/entity/de305d54-75b4-431b-adb2-eb6b9e546014',
                    '@type' => 'schema:ImageObject',
                    'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                    'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                    'description' => (string) $description,
                    'copyrightHolder' => (string) $copyrightHolder,
                    'inLanguage' => 'en',
                ],
            ],
            'modified' => $this->recordedOn->toString(),
        ];

        $body = $this->project($imageUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_age_range()
    {
        $id = 'foo';
        $eventClass = $this->getEventClass('TypicalAgeRangeUpdated');
        $typicalAgeRangeUpdated = new $eventClass($id, new AgeRange(null, new Age(18)));

        $initialDocument = new JsonDocument(
            $id,
            json_encode([
                'typicalAgeRange' => '12-14',
            ])
        );
        $this->documentRepository->save($initialDocument);

        $expectedBody = (object)[
            'typicalAgeRange' => '-18',
            'modified' => $this->recordedOn->toString(),
        ];

        $body = $this->project($typicalAgeRangeUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_deleting_of_age_range()
    {
        $id = 'foo';
        $eventClass = $this->getEventClass('TypicalAgeRangeDeleted');
        $typicalAgeRangeDeleted = new $eventClass($id);

        $initialDocument = new JsonDocument(
            $id,
            json_encode([
                'typicalAgeRange' => '-18',
            ])
        );
        $this->documentRepository->save($initialDocument);

        $expectedBody = (object)[
            'modified' => $this->recordedOn->toString(),
        ];

        $body = $this->project($typicalAgeRangeDeleted, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedBody, $body);
    }
}
