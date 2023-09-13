<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\RDF;

use CultuurNet\UDB3\Address\Address as LegacyAddress;
use CultuurNet\UDB3\Address\AddressParser;
use CultuurNet\UDB3\Address\FullAddressFormatter;
use CultuurNet\UDB3\Address\Locality as LegacyLocality;
use CultuurNet\UDB3\Address\ParsedAddress;
use CultuurNet\UDB3\Address\PostalCode as LegacyPostalCode;
use CultuurNet\UDB3\Address\Street as LegacyStreet;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Model\Serializer\Organizer\OrganizerDenormalizer;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Organizer\OrganizerProjectedToJSONLD;
use CultuurNet\UDB3\RDF\InMemoryGraphRepository;
use CultuurNet\UDB3\RdfTestCase;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use Psr\Log\LoggerInterface;

class RdfProjectorTest extends RdfTestCase
{
    private DocumentRepository $documentRepository;

    private array $expectedParsedAddresses;

    protected function setUp(): void
    {
        $this->graphRepository = new InMemoryGraphRepository();
        $this->documentRepository = new InMemoryDocumentRepository();
        $addressParser = $this->createMock(AddressParser::class);
        $logger = $this->createMock(LoggerInterface::class);

        $this->rdfProjector = new RdfProjector(
            $this->graphRepository,
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/organizers/' . $item),
            $this->documentRepository,
            new OrganizerDenormalizer(),
            $addressParser,
            $logger
        );

        $addressParser->expects($this->any())
            ->method('parse')
            ->willReturnCallback(
                fn (string $formatted): ?ParsedAddress => $this->expectedParsedAddresses[$formatted] ?? null
            );
        $this->expectedParsedAddresses = [];

        $this->expectParsedAddress(
            new LegacyAddress(
                new LegacyStreet('Kerkstraat 1'),
                new LegacyPostalCode('3271'),
                new LegacyLocality('Zichem (Scherpenheuvel-Zichem)'),
                new CountryCode('BE')
            ),
            new ParsedAddress(
                'Kerkstraat',
                '1',
                '3271',
                'Zichem (Scherpenheuvel-Zichem)'
            )
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
                    'streetAddress' => 'Kerkstraat 1',
                ],
            ],
            'geo' => [
                'latitude' => 50.9656077,
                'longitude' => 4.9502035,
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

    /**
     * @test
     */
    public function it_converts_an_organizer_with_contact_point(): void
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
            'contactPoint' => [
                'url' => [
                    'https://www.publiq.be',
                    'https://www.cultuurnet.be',
                ],
                'email' => [
                    'info@publiq.be',
                    'info@cultuurnet.be',
                ],
                'phone' => [
                    '016 10 20 30',
                    '016 10 20 40',
                    '016 99 99 99',
                ],
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

        $this->assertTurtleData($organizerId, file_get_contents(__DIR__ . '/ttl/organizer-with-contact-point.ttl'));
    }

    private function expectParsedAddress(LegacyAddress $address, ParsedAddress $parsedAddress): void
    {
        $formatted = (new FullAddressFormatter())->format($address);
        $this->expectedParsedAddresses[$formatted] = $parsedAddress;
    }

    public function getRdfDataSetName(): string
    {
        return 'organizers';
    }
}
