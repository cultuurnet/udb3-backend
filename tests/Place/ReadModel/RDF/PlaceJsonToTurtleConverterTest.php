<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\RDF;

use CultuurNet\UDB3\Address\Formatter\FullAddressFormatter;
use CultuurNet\UDB3\Address\Parser\AddressParser;
use CultuurNet\UDB3\Address\Parser\ParsedAddress;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\Serializer\Place\PlaceDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Geography\Address;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Locality;
use CultuurNet\UDB3\Model\ValueObject\Geography\PostalCode;
use CultuurNet\UDB3\Model\ValueObject\Geography\Street;
use CultuurNet\UDB3\Model\ValueObject\Moderation\WorkflowStatus;
use CultuurNet\UDB3\RDF\JsonDataCouldNotBeConverted;
use CultuurNet\UDB3\RDF\NodeUri\CRC32HashGenerator;
use CultuurNet\UDB3\RDF\NodeUri\NodeUriGenerator;
use CultuurNet\UDB3\RDF\NodeUri\ResourceFactory\RdfResourceFactoryWithoutBlankNodes;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use CultuurNet\UDB3\SampleFiles;
use Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PlaceJsonToTurtleConverterTest extends TestCase
{
    private PlaceJsonToTurtleConverter $placeJsonToTurtleConverter;
    /** @var LoggerInterface&MockObject */
    private $logger;
    private DocumentRepository $documentRepository;
    private array $expectedParsedAddresses;
    private string $placeId;
    private array $place;

    protected function setUp(): void
    {
        parent::setUp();

        $this->placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $this->place = [
            '@id' => 'https://mock.io.uitdatabank.be/places/' . $this->placeId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '8.48.0.0.0',
                    'domain' => 'eventtype',
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
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository = new InMemoryDocumentRepository();

        $addressParser = $this->createMock(AddressParser::class);
        $addressParser->expects($this->any())
            ->method('parse')
            ->willReturnCallback(
                fn (string $formatted): ?ParsedAddress => $this->expectedParsedAddresses[$formatted] ?? null
            );
        $this->expectedParsedAddresses = [];

        $this->expectParsedAddress(
            new Address(
                new Street('Martelarenlaan 1'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                new CountryCode('BE')
            ),
            new ParsedAddress(
                'Martelarenlaan',
                '1',
                '3000',
                'Leuven'
            )
        );

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->placeJsonToTurtleConverter = new PlaceJsonToTurtleConverter(
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/places/' . $item),
            new CallableIriGenerator(fn (string $item): string => 'https://mock.taxonomy.uitdatabank.be/terms/' . $item),
            $this->documentRepository,
            new PlaceDenormalizer(),
            $addressParser,
            new RdfResourceFactoryWithoutBlankNodes(new NodeUriGenerator(new CRC32HashGenerator())),
            $this->logger
        );
    }

    /**
     * @test
     */
    public function it_logs_invalid_json(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $place = [
            '@id' => 'https://mock.io.uitdatabank.be/places/' . $placeId,
        ];

        $this->documentRepository->save(new JsonDocument($placeId, Json::encode($place)));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Unable to project place d4b46fba-6433-4f86-bcb5-edeef6689fea with invalid JSON to RDF.');

        set_error_handler(
            static function ($errorNumber, $errorString) {
                restore_error_handler();
                throw new Exception($errorString, $errorNumber);
            },
            E_ALL
        );
        $this->expectException(Exception::class);

        $this->placeJsonToTurtleConverter->convert($placeId);
    }

    /**
     * @test
     */
    public function it_logs_a_place_with_dummy_location_but_missing_address(): void
    {
        $place = [
            '@id' => 'https://mock.io.uitdatabank.be/places/' . $this->placeId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '8.48.0.0.0',
                    'domain' => 'eventtype',
                ],
            ],
            'name' => [
                'nl' => 'Voorbeeld titel',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];
        $this->documentRepository->save(new JsonDocument($this->placeId, Json::encode($place)));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                'Unable to project place d4b46fba-6433-4f86-bcb5-edeef6689fea with invalid JSON to RDF.',
                [
                    'id' => $this->placeId,
                    'type' => 'place',
                    'exception' => 'Place data should contain an address.',
                ]
            );

        $this->expectException(JsonDataCouldNotBeConverted::class);

        $this->placeJsonToTurtleConverter->convert($this->placeId);
    }

    /**
     * @test
     */
    public function it_converts_a_simple_place(): void
    {
        $this->givenThereIsAPlace();

        $turtle = $this->placeJsonToTurtleConverter->convert($this->placeId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/place.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_a_place_with_translations(): void
    {
        $this->givenThereIsAPlace([
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
        ]);

        $this->expectParsedAddress(
            new Address(
                new Street('Martelarenlaan 1'),
                new PostalCode('3000'),
                new Locality('Louvain'),
                new CountryCode('BE')
            ),
            new ParsedAddress(
                'Martelarenlaan',
                '1',
                '3000',
                'Louvain'
            )
        );

        $turtle = $this->placeJsonToTurtleConverter->convert($this->placeId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/place-with-translations.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_a_place_with_coordinates(): void
    {
        $this->givenThereIsAPlace([
            'geo' => [
                'latitude' => 50.879,
                'longitude' => 4.6997,
            ],
        ]);

        $turtle = $this->placeJsonToTurtleConverter->convert($this->placeId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/place-with-coordinates.ttl'), $turtle);
    }

    /**
     * @test
     * @dataProvider workflowStatusDataProvider
     */
    public function it_converts_a_place_workflow_status(WorkflowStatus $workflowStatus, string $file): void
    {
        $this->givenThereIsAPlace([
            'workflowStatus' => $workflowStatus->toString(),
        ]);

        $turtle = $this->placeJsonToTurtleConverter->convert($this->placeId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/' . $file), $turtle);
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

    /**
     * @test
     */
    public function it_converts_a_published_place(): void
    {
        $this->givenThereIsAPlace([
            'workflowStatus' => WorkflowStatus::APPROVED()->toString(),
            'availableFrom' => '2023-04-23T12:30:15+02:00',
        ]);

        $turtle = $this->placeJsonToTurtleConverter->convert($this->placeId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/place-with-publication-date.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_a_place_with_labels(): void
    {
        $this->givenThereIsAPlace([
            'labels' => [
                'public_label_1',
                'public_label_2',
            ],
            'hiddenLabels' => [
                'hidden_label_1',
                'hidden_label_2',
            ],
        ]);

        $turtle = $this->placeJsonToTurtleConverter->convert($this->placeId);

        $this->assertEquals(SampleFiles::read(__DIR__ . '/ttl/place-with-labels.ttl'), $turtle);
    }

    private function expectParsedAddress(Address $address, ParsedAddress $parsedAddress): void
    {
        $formatted = (new FullAddressFormatter())->format($address);
        $this->expectedParsedAddresses[$formatted] = $parsedAddress;
    }

    private function givenThereIsAPlace(array $extraProperties = []): void
    {
        $place = array_merge($this->place, $extraProperties);
        $this->documentRepository->save(new JsonDocument($this->placeId, Json::encode($place)));
    }
}
