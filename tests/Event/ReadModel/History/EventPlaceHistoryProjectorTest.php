<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\History;

use CultuurNet\UDB3\Calendar\Calendar;
use CultuurNet\UDB3\Event\Events\EventCopied;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ReadModel\Relations\EventLocationHistoryRepository;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\ValueObject\Calendar\CalendarType;
use CultuurNet\UDB3\Model\ValueObject\Identity\UUID;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\ReadModel\DocumentDoesNotExist;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\Theme;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid as RamseyUuid;

class EventPlaceHistoryProjectorTest extends TestCase
{
    /** @var EventLocationHistoryRepository|MockObject */
    private $repository;

    /** @var DocumentRepository|MockObject */
    private $eventRepository;

    /** @var LoggerInterface|MockObject */
    private $logger;

    private EventPlaceHistoryProjector $projector;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(EventLocationHistoryRepository::class);
        $this->eventRepository = $this->createMock(DocumentRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->projector = new EventPlaceHistoryProjector(
            $this->repository,
            $this->eventRepository,
            $this->logger
        );
    }

    /** @test */
    public function apply_location_updated(): void
    {
        $eventId = $this->uuid4();
        $oldPlaceId = $this->uuid4();
        $newPlaceId = $this->uuid4();

        $this->eventRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($eventId->toString())
            ->willReturn($this->createMockDocument($oldPlaceId->toString()));

        $this->repository
            ->expects($this->once())
            ->method('storeEventLocationMove')
            ->with(
                $eventId,
                $oldPlaceId,
                $newPlaceId
            );

        $locationUpdated = new LocationUpdated($eventId->toString(), new LocationId($newPlaceId->toString()));

        $this->projector->handle(
            (new DomainMessageBuilder())->create($locationUpdated)
        );
    }

    /** @test */
    public function apply_event_created(): void
    {
        $eventId = $this->uuid4();
        $newPlaceId = $this->uuid4();

        $this->repository
            ->expects($this->once())
            ->method('storeEventLocationStartingPoint')
            ->with(
                $eventId,
                $newPlaceId
            );

        $eventCreated = new EventCreated(
            $eventId->toString(),
            new Language('en'),
            'Faith no More',
            new EventType('0.50.4.0.0', 'Concert'),
            new LocationId($newPlaceId->toString()),
            new Calendar(CalendarType::permanent()),
            new Theme('1.8.1.0.0', 'Rock')
        );

        $this->projector->handle(
            (new DomainMessageBuilder())->create($eventCreated)
        );
    }

    /** @test */
    public function apply_event_copied(): void
    {
        $oldEventId = $this->uuid4();
        $eventId = $this->uuid4();
        $oldPlaceId = $this->uuid4();

        $this->eventRepository
            ->expects($this->once())
            ->method('fetch')
            ->with($oldEventId->toString())
            ->willReturn($this->createMockDocument($oldPlaceId->toString()));

        $this->repository
            ->expects($this->once())
            ->method('storeEventLocationStartingPoint')
            ->with(
                $eventId,
                $oldPlaceId
            );

        $eventCopied = new EventCopied(
            $eventId->toString(),
            $oldEventId->toString(),
            new Calendar(CalendarType::permanent())
        );

        $this->projector->handle(
            (new DomainMessageBuilder())->create($eventCopied)
        );
    }

    /** @test */
    public function apply_location_updated_logs_error_when_document_does_not_exist(): void
    {
        $eventId = $this->uuid4();
        $newPlaceId = $this->uuid4();

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
            (new DomainMessageBuilder())->create($locationUpdated)
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

    /** @todo Remove with the refactor of III-6438  */
    private function uuid4(): UUID
    {
        return new UUID(RamseyUuid::uuid4()->toString());
    }
}
