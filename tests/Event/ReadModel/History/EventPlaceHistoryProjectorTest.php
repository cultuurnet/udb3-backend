<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\History;

use CultureFeed_Cdb_Xml;
use CultuurNet\UDB3\Cdb\CdbId\EventCdbIdExtractorInterface;
use CultuurNet\UDB3\Cdb\EventItemFactory;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventImportedFromUDB2;
use CultuurNet\UDB3\Event\Events\EventUpdatedFromUDB2;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\MajorInfoUpdated;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventPlaceHistoryRepository;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Identity\Uuid;
use CultuurNet\UDB3\Model\ValueObject\Calendar\OpeningHours\OpeningHours;
use CultuurNet\UDB3\Model\ValueObject\Calendar\PermanentCalendar;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\Category;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryDomain;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryID;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Category\CategoryLabel;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\SampleFiles;
use DateTimeImmutable;
use DateTimeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class EventPlaceHistoryProjectorTest extends TestCase
{
    private const DATE_TIME_FORMAT = DateTimeInterface::ATOM;
    private const DATE_TIME_VALUE = '2024-1-1T12:30:00+00:00';

    /** @var EventPlaceHistoryRepository|MockObject */
    private $repository;

    /** @var DocumentRepository|MockObject */
    private $eventRepository;

    /** @var LoggerInterface|MockObject */
    private $logger;

    /** @var EventCdbIdExtractorInterface|MockObject */
    private $eventCdbIdExtractor;

    private EventPlaceHistoryProjector $projector;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EventPlaceHistoryRepository::class);
        $this->eventRepository = $this->createMock(DocumentRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventCdbIdExtractor = $this->createMock(EventCdbIdExtractorInterface::class);

        $this->projector = new EventPlaceHistoryProjector(
            $this->repository,
            $this->eventRepository,
            $this->eventCdbIdExtractor,
            $this->logger
        );
    }

    /** @test */
    public function apply_location_updated(): void
    {
        $eventId = Uuid::uuid4();
        $oldPlaceId = Uuid::uuid4();
        $newPlaceId = Uuid::uuid4();

        $this->eventRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($eventId->toString())
            ->willReturn($this->createMockDocument($oldPlaceId->toString()));

        $this->repository
            ->expects($this->once())
            ->method('storeEventPlaceMove')
            ->with(
                $eventId,
                $oldPlaceId,
                $newPlaceId,
                DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, self::DATE_TIME_VALUE)
            );

        $locationUpdated = new LocationUpdated($eventId->toString(), new LocationId($newPlaceId->toString()));

        $this->projector->handle(
            (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($locationUpdated)
        );
    }

    /** @test */
    public function apply_major_info_updated(): void
    {
        $eventId = Uuid::uuid4();
        $oldPlaceId = Uuid::uuid4();
        $newPlaceId = Uuid::uuid4();

        $this->eventRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($eventId->toString())
            ->willReturn($this->createMockDocument($oldPlaceId->toString()));

        $this->repository
            ->expects($this->once())
            ->method('storeEventPlaceMove')
            ->with(
                $eventId,
                $oldPlaceId,
                $newPlaceId,
                DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, self::DATE_TIME_VALUE)
            );

        $majorInfoUpdated = new MajorInfoUpdated(
            $eventId->toString(),
            'title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId($newPlaceId->toString()),
            new PermanentCalendar(new OpeningHours())
        );

        $this->projector->handle(
            (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($majorInfoUpdated)
        );
    }

    /** @test */
    public function apply_major_info_updated_with_dummy_location(): void
    {
        $eventId = Uuid::uuid4();
        $newPlaceId = Uuid::uuid4();

        $this->eventRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($eventId->toString())
            ->willReturn($this->givenAnEventWithADummyPlace($eventId));

        $this->repository
            ->expects($this->once())
            ->method('storeEventPlaceStartingPoint')
            ->with(
                $eventId,
                $newPlaceId,
                DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, self::DATE_TIME_VALUE)
            );

        $majorInfoUpdated = new MajorInfoUpdated(
            $eventId->toString(),
            'title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId($newPlaceId->toString()),
            new PermanentCalendar(new OpeningHours())
        );

        $this->projector->handle(
            (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($majorInfoUpdated)
        );
    }

    /** @test */
    public function prevent_apply_major_info_updated_when_location_did_not_change(): void
    {
        $eventId = Uuid::uuid4();
        $oldPlaceId = Uuid::uuid4();
        $newPlaceId = $oldPlaceId;

        $this->eventRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($eventId->toString())
            ->willReturn($this->createMockDocument($oldPlaceId->toString()));

        $this->repository
            ->expects($this->never())
            ->method('storeEventPlaceMove');

        $majorInfoUpdated = new MajorInfoUpdated(
            $eventId->toString(),
            'title',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId($newPlaceId->toString()),
            new PermanentCalendar(new OpeningHours())
        );

        $this->projector->handle(
            (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($majorInfoUpdated)
        );
    }

    /** @test */
    public function apply_event_created(): void
    {
        $eventId = Uuid::uuid4();
        $newPlaceId = Uuid::uuid4();

        $this->repository
            ->expects($this->once())
            ->method('storeEventPlaceStartingPoint')
            ->with(
                $eventId,
                $newPlaceId,
                DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, self::DATE_TIME_VALUE)
            );

        $eventCreated = new EventCreated(
            $eventId->toString(),
            new Language('en'),
            'Faith no More',
            new Category(new CategoryID('0.50.4.0.0'), new CategoryLabel('Concert'), CategoryDomain::eventType()),
            new LocationId($newPlaceId->toString()),
            new PermanentCalendar(new OpeningHours()),
            new Category(new CategoryID('1.8.1.0.0'), new CategoryLabel('Rock'), CategoryDomain::theme())
        );

        $this->projector->handle(
            (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($eventCreated)
        );
    }

    /** @test */
    public function apply_event_copied(): void
    {
        $oldEventId = Uuid::uuid4();
        $eventId = Uuid::uuid4();
        $oldPlaceId = Uuid::uuid4();

        $this->eventRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($oldEventId->toString())
            ->willReturn($this->createMockDocument($oldPlaceId->toString()));

        $this->repository
            ->expects($this->once())
            ->method('storeEventPlaceStartingPoint')
            ->with(
                $eventId,
                $oldPlaceId,
                DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, self::DATE_TIME_VALUE)
            );

        $eventCopied = new EventCopied(
            $eventId->toString(),
            $oldEventId->toString(),
            new PermanentCalendar(new OpeningHours())
        );

        $this->projector->handle(
            (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($eventCopied)
        );
    }

    /** @test */
    public function apply_event_copied_with_dummy_location(): void
    {
        $oldEventId = Uuid::uuid4();
        $eventId = Uuid::uuid4();

        $this->eventRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($oldEventId->toString())
            ->willReturn($this->givenAnEventWithADummyPlace($oldEventId));

        $this->repository
            ->expects($this->never())
            ->method('storeEventPlaceStartingPoint');

        $eventCopied = new EventCopied(
            $eventId->toString(),
            $oldEventId->toString(),
            new PermanentCalendar(new OpeningHours())
        );

        $this->projector->handle(
            (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($eventCopied)
        );
    }

    /** @test */
    public function apply_location_updated_logs_error_when_document_does_not_exist(): void
    {
        $eventId = Uuid::uuid4();
        $newPlaceId = Uuid::uuid4();

        $this->eventRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($eventId->toString())
            ->willThrowException(new DocumentDoesNotExist('Document not found'));

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains('Failed to store location updated: Document not found'));

        $locationUpdated = new LocationUpdated($eventId->toString(), new LocationId($newPlaceId->toString()));
        $this->projector->handle(
            (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($locationUpdated)
        );
    }

    /** @test */
    public function apply_location_updated_with_dummy_location(): void
    {
        $eventId = Uuid::uuid4();
        $newPlaceId = Uuid::uuid4();

        $this->eventRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($eventId->toString())
            ->willReturn($this->givenAnEventWithADummyPlace($eventId));

        $this->repository
            ->expects($this->once())
            ->method('storeEventPlaceStartingPoint')
            ->with(
                $eventId,
                $newPlaceId,
                DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, self::DATE_TIME_VALUE)
            );

        $locationUpdated = new LocationUpdated($eventId->toString(), new LocationId($newPlaceId->toString()));

        $this->projector->handle(
            (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($locationUpdated)
        );
    }

    /** @test */
    public function apply_event_updated_from_udb2(): void
    {
        $eventId = Uuid::uuid4();
        $oldPlaceId = Uuid::uuid4();
        $newPlaceId = new Uuid('28d2900d-f784-4d04-8d66-5b93900c6f9c');

        $this->eventRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($eventId->toString())
            ->willReturn($this->createMockDocument($oldPlaceId->toString()));

        $this->repository
            ->expects($this->once())
            ->method('storeEventPlaceMove')
            ->with(
                $eventId,
                $oldPlaceId,
                $newPlaceId,
                DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, self::DATE_TIME_VALUE)
            );

        $eventUpdatedFromUDB2 = new EventUpdatedFromUDB2(
            $eventId->toString(),
            SampleFiles::read(__DIR__ . '/../../samples/event_with_existing_location.cdbxml.xml'),
            CultureFeed_Cdb_Xml::namespaceUriForVersion('3.2')
        );

        $this->eventCdbIdExtractor->expects($this->once())
            ->method('getRelatedPlaceCdbId')
            ->with(EventItemFactory::createEventFromCdbXml(
                $eventUpdatedFromUDB2->getCdbXmlNamespaceUri(),
                $eventUpdatedFromUDB2->getCdbXml()
            ))
            ->willReturn($newPlaceId->toString());

        $this->projector->handle(
            (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($eventUpdatedFromUDB2)
        );
    }

    /** @test */
    public function apply_event_updated_from_udb2_with_a_dummy_location(): void
    {
        $eventId = Uuid::uuid4();
        $oldPlaceId = Uuid::uuid4();

        $this->eventRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($eventId->toString())
            ->willReturn($this->createMockDocument($oldPlaceId->toString()));

        $this->repository
            ->expects($this->never())
            ->method('storeEventPlaceMove');

        $eventUpdatedFromUDB2 = new EventUpdatedFromUDB2(
            $eventId->toString(),
            SampleFiles::read(__DIR__ . '/../../samples/event_with_dummy_location.cdbxml.xml'),
            CultureFeed_Cdb_Xml::namespaceUriForVersion('3.2')
        );

        $this->eventCdbIdExtractor->expects($this->once())
            ->method('getRelatedPlaceCdbId')
            ->with(EventItemFactory::createEventFromCdbXml(
                $eventUpdatedFromUDB2->getCdbXmlNamespaceUri(),
                $eventUpdatedFromUDB2->getCdbXml()
            ))
            ->willReturn(null);

        $this->projector->handle(
            (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($eventUpdatedFromUDB2)
        );
    }

    /** @test */
    public function prevent_apply_event_updated_from_udb2_when_location_did_not_change(): void
    {
        $eventId = Uuid::uuid4();
        $oldPlaceId = new Uuid('28d2900d-f784-4d04-8d66-5b93900c6f9c');

        $this->eventRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($eventId->toString())
            ->willReturn($this->createMockDocument($oldPlaceId->toString()));

        $this->repository
            ->expects($this->never())
            ->method('storeEventPlaceMove');

        $eventUpdatedFromUDB2 = new EventUpdatedFromUDB2(
            $eventId->toString(),
            SampleFiles::read(__DIR__ . '/../../samples/event_with_existing_location.cdbxml.xml'),
            CultureFeed_Cdb_Xml::namespaceUriForVersion('3.2')
        );

        $this->projector->handle(
            (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($eventUpdatedFromUDB2)
        );
    }

    /** @test */
    public function apply_event_imported_from_udb2(): void
    {
        $eventId = Uuid::uuid4();
        $newPlaceId = new Uuid('28d2900d-f784-4d04-8d66-5b93900c6f9c');

        $this->repository
            ->expects($this->once())
            ->method('storeEventPlaceStartingPoint')
            ->with(
                $eventId,
                $newPlaceId,
                DateTimeImmutable::createFromFormat(self::DATE_TIME_FORMAT, self::DATE_TIME_VALUE)
            );

        $eventImportedFromUDB2 = new EventImportedFromUDB2(
            $eventId->toString(),
            SampleFiles::read(__DIR__ . '/../../samples/event_with_existing_location.cdbxml.xml'),
            CultureFeed_Cdb_Xml::namespaceUriForVersion('3.2')
        );

        $this->eventCdbIdExtractor->expects($this->once())
            ->method('getRelatedPlaceCdbId')
            ->with(EventItemFactory::createEventFromCdbXml(
                $eventImportedFromUDB2->getCdbXmlNamespaceUri(),
                $eventImportedFromUDB2->getCdbXml()
            ))
            ->willReturn($newPlaceId->toString());

        $this->projector->handle(
            (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($eventImportedFromUDB2)
        );
    }

    /** @test */
    public function apply_event_imported_from_udb2_with_a_dummy_location(): void
    {
        $eventId = Uuid::uuid4();

        $this->repository
            ->expects($this->never())
            ->method('storeEventPlaceStartingPoint');

        $eventImportedFromUDB2 = new EventImportedFromUDB2(
            $eventId->toString(),
            SampleFiles::read(__DIR__ . '/../../samples/event_with_dummy_location.cdbxml.xml'),
            CultureFeed_Cdb_Xml::namespaceUriForVersion('3.2')
        );

        $this->eventCdbIdExtractor->expects($this->once())
            ->method('getRelatedPlaceCdbId')
            ->with(EventItemFactory::createEventFromCdbXml(
                $eventImportedFromUDB2->getCdbXmlNamespaceUri(),
                $eventImportedFromUDB2->getCdbXml()
            ))
            ->willReturn(null);

        $this->projector->handle(
            (new DomainMessageBuilder())->setRecordedOnFromDateTimeString(self::DATE_TIME_VALUE)->create($eventImportedFromUDB2)
        );
    }

    private function createMockDocument(string $placeId): JsonDocument
    {
        return new JsonDocument($placeId, Json::encode([
            'location' => [
                '@id' => sprintf('https://io.uitdatabank.be/place/%s', $placeId),
            ],
        ]));
    }

    private function givenAnEventWithADummyPlace(Uuid $oldEventId): JsonDocument
    {
        return new JsonDocument($oldEventId->toString(), Json::encode([
            'location' => ['title' => 'old dummy place with no place id'],
        ]));
    }
}
