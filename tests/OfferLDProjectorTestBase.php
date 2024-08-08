<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Domain\DateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Media\Image;
use CultuurNet\UDB3\Media\Properties\Description as MediaDescription;
use CultuurNet\UDB3\Media\Properties\MIMEType;
use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\MediaObject\CopyrightHolder;
use CultuurNet\UDB3\Model\ValueObject\Web\Url;
use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\ValueObject\MultilingualString;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Base test  case class for testing common Offer JSON-LD projector
 * functionality.
 */
abstract class OfferLDProjectorTestBase extends TestCase
{
    protected InMemoryDocumentRepository $documentRepository;

    protected EventListener $projector;

    protected string $eventNamespace;

    protected RecordedOn $recordedOn;

    /**
     * @var DocumentRepository|MockObject
     */
    protected $organizerRepository;


    public function __construct(?string $name, array $data, $dataName, string $eventNamespace)
    {
        parent::__construct($name, $data, $dataName);

        $this->eventNamespace = $eventNamespace;

        $this->recordedOn = RecordedOn::fromBroadwayDateTime(DateTime::now());
    }

    private function getEventClass(string $className): string
    {
        return $this->eventNamespace . '\\Events\\' . $className;
    }

    protected function setUp(): void
    {
        $this->documentRepository = new InMemoryDocumentRepository();

        $this->organizerRepository = $this->createMock(DocumentRepository::class);
    }

    protected function project(
        object $event,
        string $entityId,
        Metadata $metadata = null,
        DateTime $dateTime = null,
        int $playhead = 1
    ): stdClass {
        if (null === $metadata) {
            $metadata = new Metadata();
        }

        if (null === $dateTime) {
            $dateTime = DateTime::now();
        }

        $this->projector->handle(
            new DomainMessage(
                $entityId,
                $playhead,
                $metadata,
                $event,
                $dateTime
            )
        );

        return $this->getBody($entityId);
    }

    protected function getBody(string $id): stdClass
    {
        $document = $this->documentRepository->fetch($id);
        return $document->getBody();
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_booking_info(): void
    {
        $id = 'foo';
        $url = 'http://www.google.be';
        $urlLabel = new MultilingualString(new Language('nl'), 'Google');
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
            'playhead' => 3,
            'completeness' => 3,
        ];

        $body = $this->project($bookingInfoUpdated, $id, null, $this->recordedOn->toBroadwayDateTime(), 3);

        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_contact_point(): void
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
            'playhead' => 1,
            'completeness' => 3,
        ];

        $this->assertEquals(
            $expectedBody,
            $body
        );
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_description(): void
    {
        $description = new Description('description');
        $id = 'foo';
        $eventClass = $this->getEventClass('DescriptionUpdated');
        $descriptionUpdated = new $eventClass($id, $description);

        $initialDocument = new JsonDocument(
            $id,
            Json::encode(
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
                'nl' => $description->toString(),
            ],
            'languages' => ['nl'],
            'completedLanguages' => ['nl'],
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 12,
        ];

        $body = $this->project($descriptionUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_adding_of_an_image(): void
    {
        $id = 'foo';
        $imageId = new UUID('de305d54-75b4-431b-adb2-eb6b9e546014');
        $description = new MediaDescription('Some description.');
        $copyrightHolder = new CopyrightHolder('Dirk Dirkington');
        $type = new MIMEType('image/png');
        $location = new Url('http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png');
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
                    'id' => 'de305d54-75b4-431b-adb2-eb6b9e546014',
                    'contentUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                    'thumbnailUrl' => 'http://foo.bar/media/de305d54-75b4-431b-adb2-eb6b9e546014.png',
                    'description' => $description->toString(),
                    'copyrightHolder' => $copyrightHolder->toString(),
                    'inLanguage' => 'en',
                ],
            ],
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 8,
        ];

        $body = $this->project($imageAdded, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_editing_of_an_image(): void
    {
        $id = 'foo';
        $imageId = new UUID('de305d54-75b4-431b-adb2-eb6b9e546014');
        $description = 'Some description.';
        $copyrightHolder = new CopyrightHolder('Dirk Dirkington');
        $eventClass = $this->getEventClass('ImageUpdated');
        $imageUpdated = new $eventClass($id, $imageId->toString(), $description, $copyrightHolder->toString());

        $initialDocument = new JsonDocument(
            $id,
            Json::encode([
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
                    'copyrightHolder' => $copyrightHolder->toString(),
                    'inLanguage' => 'en',
                ],
            ],
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 8,
        ];

        $body = $this->project($imageUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_updating_of_age_range(): void
    {
        $id = 'foo';
        $eventClass = $this->getEventClass('TypicalAgeRangeUpdated');
        $typicalAgeRangeUpdated = new $eventClass($id, new AgeRange(null, new Age(18)));

        $initialDocument = new JsonDocument(
            $id,
            Json::encode([
                'typicalAgeRange' => '12-14',
            ])
        );
        $this->documentRepository->save($initialDocument);

        $expectedBody = (object)[
            'typicalAgeRange' => '0-18',
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 12,
        ];

        $body = $this->project($typicalAgeRangeUpdated, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedBody, $body);
    }

    /**
     * @test
     */
    public function it_projects_the_deleting_of_age_range(): void
    {
        $id = 'foo';
        $eventClass = $this->getEventClass('TypicalAgeRangeDeleted');
        $typicalAgeRangeDeleted = new $eventClass($id);

        $initialDocument = new JsonDocument(
            $id,
            Json::encode([
                'typicalAgeRange' => '-18',
            ])
        );
        $this->documentRepository->save($initialDocument);

        $expectedBody = (object)[
            'typicalAgeRange' => '-',
            'modified' => $this->recordedOn->toString(),
            'playhead' => 1,
            'completeness' => 12,
        ];

        $body = $this->project($typicalAgeRangeDeleted, $id, null, $this->recordedOn->toBroadwayDateTime());

        $this->assertEquals($expectedBody, $body);
    }
}
