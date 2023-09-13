<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\RDF;

use CultuurNet\UDB3\Address\Address as LegacyAddress;
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
use CultuurNet\UDB3\RdfTestCase;
use CultuurNet\UDB3\ReadModel\JsonDocument;

class RdfProjectorTest extends RdfTestCase
{
    private array $expectedParsedAddresses;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rdfProjector = new RdfProjector(
            $this->graphRepository,
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/places/' . $item),
            new CallableIriGenerator(fn (string $item): string => 'https://mock.taxonomy.uitdatabank.be/terms/' . $item),
            $this->documentRepository,
            new PlaceDenormalizer(),
            $this->addressParser,
            $this->logger
        );

        $this->addressParser->expects($this->any())
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
    public function it_logs_invalid_json(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $place = [
            '@id' => 'https://mock.io.uitdatabank.be/places/' . $placeId,
        ];

        $this->documentRepository->save(new JsonDocument($placeId, json_encode($place)));

        $this->logger->expects($this->once())
            ->method('warning')
            ->with('Unable to project place d4b46fba-6433-4f86-bcb5-edeef6689fea with invalid JSON to RDF.');

        $this->project(
            $placeId,
            [
                new PlaceProjectedToJSONLD($placeId, 'https://mock.io.uitdatabank.be/places/' . $placeId),
            ]
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
                    'domain' => 'eventtype',
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
            'created' => '2023-01-01T12:30:15+01:00',
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
            'geo' => [
                'latitude' => 50.879,
                'longitude' => 4.6997,
            ],
            'created' => '2023-01-01T12:30:15+01:00',
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

    /**
     * @test
     */
    public function it_converts_a_published_place(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $place = [
            '@id' => 'https://mock.io.uitdatabank.be/places/' . $placeId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'workflowStatus' => WorkflowStatus::APPROVED()->toString(),
            'availableFrom' => '2023-04-23T12:30:15+02:00',
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
        ];

        $this->documentRepository->save(new JsonDocument($placeId, json_encode($place)));

        $this->project(
            $placeId,
            [
                new PlaceProjectedToJSONLD($placeId, 'https://mock.io.uitdatabank.be/places/' . $placeId),
            ]
        );

        $this->assertTurtleData($placeId, file_get_contents(__DIR__ . '/ttl/place-with-publication-date.ttl'));
    }

    private function expectParsedAddress(LegacyAddress $address, ParsedAddress $parsedAddress): void
    {
        $formatted = (new FullAddressFormatter())->format($address);
        $this->expectedParsedAddresses[$formatted] = $parsedAddress;
    }

    public function getRdfDataSetName(): string
    {
        return 'places';
    }
}
