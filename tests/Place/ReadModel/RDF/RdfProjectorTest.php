<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\RDF;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\Address as LegacyAddress;
use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\Address\FullAddressFormatter;
use CultuurNet\UDB3\Address\Locality as LegacyLocality;
use CultuurNet\UDB3\Address\ParsedAddress;
use CultuurNet\UDB3\Address\PostalCode as LegacyPostalCode;
use CultuurNet\UDB3\Address\Street as LegacyStreet;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\InMemoryGraphRepository;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use DateTime;
use EasyRdf\Serialiser\Turtle;
use PHPUnit\Framework\TestCase;

class RdfProjectorTest extends TestCase
{
    private GraphRepository $graphRepository;

    private DocumentRepository $documentRepository;

    private RdfProjector $rdfProjector;

    private array $expectedParsedAddresses;

    protected function setUp(): void
    {
        $this->graphRepository = new InMemoryGraphRepository();
        $this->documentRepository = new InMemoryDocumentRepository();
        $addressParser = $this->createMock(AddressParser::class);

        $this->rdfProjector = new RdfProjector(
            $this->graphRepository,
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/places/' . $item),
            $this->documentRepository,
            new PlaceDenormalizer(),
            $addressParser
        );

        $addressParser->expects($this->any())
            ->method('parse')
            ->willReturnCallback(
                fn (string $formatted): ?ParsedAddress => $this->expectedParsedAddresses[$formatted] ?? null
            );
        $this->expectedParsedAddresses = [];

        $this->expectParsedAddress(
            new LegacyAddress(
                new LegacyStreet('Martelarenlaan 1'),
                new LegacyPostalCode('3000'),
                new LegacyLocality('Leuven'),
                new CountryCode('BE')
            ),
            new ParsedAddress(
                'Martelarenlaan',
                '1',
                '3000',
                'Leuven'
            )
        );
    }

    /**
     * @test
     */
    public function it_converts_a_simple_place(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $place = [
            '@id' => 'https://mock.io.uitdatabank.be/places/' . $placeId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '8.48.0.0.0',
                ],
            ],
            'name' => [
                'nl' => 'Voorbeeld titel',
            ],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Martelarenlaan 1',
                    'postalCode' => '3000',
                    'addressLocality' => 'Leuven',
                    'addressCountry' => 'BE',
                ],
            ],
        ];

        $this->documentRepository->save(new JsonDocument($placeId, json_encode($place)));

        $this->project(
            $placeId,
            [
                new PlaceProjectedToJSONLD($placeId, 'https://mock.io.uitdatabank.be/places/' . $placeId),
            ]
        );

        $this->assertTurtleData($placeId, file_get_contents(__DIR__ . '/ttl/place.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_a_place_with_translations(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $place = [
            '@id' => 'https://mock.io.uitdatabank.be/places/' . $placeId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '8.48.0.0.0',
                ],
            ],
            'name' => [
                'nl' => 'Voorbeeld titel',
                'en' => 'Example title',
            ],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Martelarenlaan 1',
                    'postalCode' => '3000',
                    'addressLocality' => 'Leuven',
                    'addressCountry' => 'BE',
                ],
                'fr' => [
                    'streetAddress' => 'Martelarenlaan 1',
                    'postalCode' => '3000',
                    'addressLocality' => 'Louvain',
                    'addressCountry' => 'BE',
                ],
            ],
        ];

        $this->documentRepository->save(new JsonDocument($placeId, json_encode($place)));

        $this->expectParsedAddress(
            new LegacyAddress(
                new LegacyStreet('Martelarenlaan 1'),
                new LegacyPostalCode('3000'),
                new LegacyLocality('Louvain'),
                new CountryCode('BE')
            ),
            new ParsedAddress(
                'Martelarenlaan',
                '1',
                '3000',
                'Louvain'
            )
        );

        $this->project(
            $placeId,
            [
                new PlaceProjectedToJSONLD($placeId, 'https://mock.io.uitdatabank.be/places/' . $placeId),
            ]
        );

        $this->assertTurtleData($placeId, file_get_contents(__DIR__ . '/ttl/place-with-translations.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_a_place_with_coordinates(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $place = [
            '@id' => 'https://mock.io.uitdatabank.be/places/' . $placeId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '8.48.0.0.0',
                ],
            ],
            'name' => [
                'nl' => 'Voorbeeld titel',
            ],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Martelarenlaan 1',
                    'postalCode' => '3000',
                    'addressLocality' => 'Leuven',
                    'addressCountry' => 'BE',
                ],
            ],
            'geo' => [
                'latitude' => 50.879,
                'longitude' => 4.6997,
            ],
        ];

        $this->documentRepository->save(new JsonDocument($placeId, json_encode($place)));

        $this->project(
            $placeId,
            [
                new PlaceProjectedToJSONLD($placeId, 'https://mock.io.uitdatabank.be/places/' . $placeId),
            ]
        );

        $this->assertTurtleData($placeId, file_get_contents(__DIR__ . '/ttl/place-with-coordinates.ttl'));
    }

    /**
     * @test
     * @dataProvider workflowStatusDataProvider
     */
    public function it_converts_a_place_workflow_status(WorkflowStatus $workflowStatus, string $file): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $place = [
            '@id' => 'https://mock.io.uitdatabank.be/places/' . $placeId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'workflowStatus' => $workflowStatus->toString(),
            'terms' => [
                [
                    'id' => '8.48.0.0.0',
                ],
            ],
            'name' => [
                'nl' => 'Voorbeeld titel',
            ],
            'address' => [
                'nl' => [
                    'streetAddress' => 'Martelarenlaan 1',
                    'postalCode' => '3000',
                    'addressLocality' => 'Leuven',
                    'addressCountry' => 'BE',
                ],
            ],
        ];

        $this->documentRepository->save(new JsonDocument($placeId, json_encode($place)));

        $this->project(
            $placeId,
            [
                new PlaceProjectedToJSONLD($placeId, 'https://mock.io.uitdatabank.be/places/' . $placeId),
            ]
        );

        $this->assertTurtleData($placeId, file_get_contents(__DIR__ . '/ttl/' . $file));
    }

    public function workflowStatusDataProvider(): array
    {
        return [
            'draft' => [
                'workflowStatus' => WorkflowStatus::DRAFT(),
                'file' => 'place.ttl',
            ],
            'ready for validation' => [
                'workflowStatus' => WorkflowStatus::READY_FOR_VALIDATION(),
                'file' => 'place-with-status-ready-for-validation.ttl',
            ],
            'approved' => [
                'workflowStatus' => WorkflowStatus::APPROVED(),
                'file' => 'place-with-status-approved.ttl',
            ],
            'rejected' => [
                'workflowStatus' => WorkflowStatus::REJECTED(),
                'file' => 'place-with-status-rejected.ttl',
            ],
            'deleted' => [
                'workflowStatus' => WorkflowStatus::DELETED(),
                'file' => 'place-with-status-deleted.ttl',
            ],
        ];
    }

    private function expectParsedAddress(LegacyAddress $address, ParsedAddress $parsedAddress): void
    {
        $formatted = (new FullAddressFormatter())->format($address);
        $this->expectedParsedAddresses[$formatted] = $parsedAddress;
    }

    private function project(string $placeId, array $events): void
    {
        $playhead = -1;
        $recordedOn = new DateTime('2022-12-31T12:30:15+01:00');
        foreach ($events as $event) {
            $playhead++;
            $recordedOn->modify('+1 day');
            $domainMessage = new DomainMessage(
                $placeId,
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
        $uri = 'https://mock.data.publiq.be/places/' . $placeId;
        $actualTurtleData = (new Turtle())->serialise($this->graphRepository->get($uri), 'turtle');
        $this->assertEquals(trim($expectedTurtleData), trim($actualTurtleData));
    }
}
