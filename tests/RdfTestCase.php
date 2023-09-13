<?php

declare(strict_types=1);

namespace CultuurNet\UDB3;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use Broadway\EventHandling\EventListener;
use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\InMemoryGraphRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use DateTime;
use EasyRdf\Serialiser\Turtle;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

abstract class RdfTestCase extends TestCase
{
    protected GraphRepository $graphRepository;
    protected DocumentRepository $documentRepository;
    /** @var AddressParser&MockObject */
    protected $addressParser;
    /** @var LoggerInterface&MockObject */
    protected $logger;

    protected EventListener $rdfProjector;

    abstract protected function getRdfDataSetName(): string;

    protected function setUp(): void
    {
        parent::setUp();

        $this->graphRepository = new InMemoryGraphRepository();
        $this->documentRepository = new InMemoryDocumentRepository();
        $this->addressParser = $this->createMock(AddressParser::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    protected function project(string $organizerId, array $events): void
    {
        $playhead = -1;
        $recordedOn = new DateTime('2022-12-31T12:30:15+01:00');
        foreach ($events as $event) {
            $playhead++;
            $recordedOn->modify('+1 day');
            $domainMessage = new DomainMessage(
                $organizerId,
                $playhead,
                new Metadata(),
                $event,
                BroadwayDateTime::fromString($recordedOn->format(DateTime::ATOM))
            );
            $this->rdfProjector->handle($domainMessage);
        }
    }

    protected function assertTurtleData(string $itemId, string $expectedTurtleData): void
    {
        $uri = 'https://mock.data.publiq.be/' . $this->getRdfDataSetName() . '/' . $itemId;
        $actualTurtleData = (new Turtle())->serialise($this->graphRepository->get($uri), 'turtle');
        $this->assertEquals(trim($expectedTurtleData), trim($actualTurtleData));
    }
}
