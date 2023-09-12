<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\RDF;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Model\Serializer\Organizer\OrganizerDenormalizer;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\InMemoryGraphRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use DateTime;
use EasyRdf\Serialiser\Turtle;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class RdfProjectorTest extends TestCase
{
    private GraphRepository $graphRepository;

    private DocumentRepository $documentRepository;

    private RdfProjector $rdfProjector;

    /**
     * @var LoggerInterface|MockObject
     */
    private $logger;

    protected function setUp(): void
    {
        $this->graphRepository = new InMemoryGraphRepository();
        $this->documentRepository = new InMemoryDocumentRepository();
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->rdfProjector = new RdfProjector(
            $this->graphRepository,
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/organizers/' . $item),
            $this->documentRepository,
            new OrganizerDenormalizer(),
            $this->logger
        );
    }

    /**
     * @test
     */
    public function it_converts_a_simple_organizer(): void
    {
        $organizerId = '56f1efdb-fe25-44f6-b9d7-4a6a836799d7';

        $organizer = [
            '@id' => 'https://mock.io.uitdatabank.be/organizers/' . $organizerId,
            'mainLanguage' => 'nl',
            'url' => 'https://www.publiq.be',
            'name' => [
                'nl' => 'publiq VZW',
                'en' => 'publiq NPO',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($organizerId, json_encode($organizer)));

        $this->project(
            $organizerId,
            [
                new OrganizerProjectedToJSONLD($organizerId, 'https://mock.io.uitdatabank.be/organizer/' . $organizerId),
            ]
        );

        $this->assertTurtleData($organizerId, file_get_contents(__DIR__ . '/ttl/organizer.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_organizer_with_address(): void
    {
        $organizerId = '56f1efdb-fe25-44f6-b9d7-4a6a836799d7';

        $organizer = [
            '@id' => 'https://mock.io.uitdatabank.be/organizers/' . $organizerId,
            'mainLanguage' => 'nl',
            'url' => 'https://www.publiq.be',
            'name' => [
                'nl' => 'publiq VZW',
                'en' => 'publiq NPO',
            ],
            'address' => [
                'nl' => [
                    'addressCountry' => 'BE',
                    'addressLocality' => 'Zichem (Scherpenheuvel-Zichem)',
                    'postalCode' => '3271',
                    'streetAddress' => 'Kerkstraat 1'
                ]
            ],
            'geo' => [
                'latitude' => 50.9656077,
                'longitude' => 4.9502035
            ],
            'created' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($organizerId, json_encode($organizer)));

        $this->project(
            $organizerId,
            [
                new OrganizerProjectedToJSONLD($organizerId, 'https://mock.io.uitdatabank.be/organizer/' . $organizerId),
            ]
        );

        $this->assertTurtleData($organizerId, file_get_contents(__DIR__ . '/ttl/organizer-with-address.ttl'));
    }

    private function project(string $organizerId, array $events): void
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

    private function assertTurtleData(string $placeId, string $expectedTurtleData): void
    {
        $uri = 'https://mock.data.publiq.be/organizers/' . $placeId;
        $actualTurtleData = (new Turtle())->serialise($this->graphRepository->get($uri), 'turtle');
        $this->assertEquals(trim($expectedTurtleData), trim($actualTurtleData));
    }
}
