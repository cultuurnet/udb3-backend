<?php declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\ReadModel\JSONLD;

use CultuurNet\UDB3\Event\ValueObjects\Status;
use CultuurNet\UDB3\Event\ValueObjects\StatusReason;
use CultuurNet\UDB3\Event\ValueObjects\StatusType;
use CultuurNet\UDB3\EventSourcing\DomainMessageBuilder;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\Offer\Events\StatusUpdated;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StatusUpdatedProjectorTest extends TestCase
{
    /**
     * @var DomainMessageBuilder
     */
    private $domainMessageBuilder;

    /**
     * @var DocumentRepository
     */
    private $eventRepository;

    /**
     * @var DocumentRepository
     */
    private $placeRepository;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    /**
     * @var StatusUpdatedProjector
     */
    private $statusUpdatedProjector;

    protected function setUp(): void
    {
        $this->domainMessageBuilder = new DomainMessageBuilder();

        $this->eventRepository = new InMemoryDocumentRepository();
        $this->placeRepository = new InMemoryDocumentRepository();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->statusUpdatedProjector = new StatusUpdatedProjector(
            $this->eventRepository,
            $this->placeRepository,
            $this->logger
        );
    }

    /**
     * @test
     */
    public function it_requires_an_existing_event_or_place(): void
    {
        $this->logger->expects($this->once())
            ->method('warning')
            ->with('No place or event found with id 542d8328-0051-4890-afbb-38b0cc8dae07 to apply StatusUpdated.');

        $statusUpdated = new StatusUpdated(
            '542d8328-0051-4890-afbb-38b0cc8dae07',
            new Status(
                StatusType::unavailable(),
                [
                    new StatusReason(new Language('nl'), 'Nog steeds geen concerten mogelijk.'),
                    new StatusReason(new Language('en'), 'Still no concerts allowed.'),
                ]
            )
        );

        $this->statusUpdatedProjector->handle(
            $this->domainMessageBuilder->create($statusUpdated)
        );
    }
}
