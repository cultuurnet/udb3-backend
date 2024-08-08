<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\ReadModel\RDF;

use CultuurNet\UDB3\Address\Parser\AddressParser;
use CultuurNet\UDB3\Address\Parser\ParsedAddress;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\Model\Serializer\Organizer\OrganizerDenormalizer;
use CultuurNet\UDB3\ReadModel\DocumentRepository;
use CultuurNet\UDB3\ReadModel\InMemoryDocumentRepository;
use CultuurNet\UDB3\ReadModel\JsonDocument;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class OrganizerJsonToTurtleConverterTest extends TestCase
{
    private DocumentRepository $documentRepository;

    private OrganizerJsonToTurtleConverter $organizerJsonToTurtleConverter;

    private string $organizerId;
    private array $organizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organizerId = '56f1efdb-fe25-44f6-b9d7-4a6a836799d7';
        $this->organizer = [
            '@id' => 'https://mock.io.uitdatabank.be/organizers/' . $this->organizerId,
            'mainLanguage' => 'nl',
            'url' => 'https://www.publiq.be',
            'name' => [
                'nl' => 'publiq VZW',
                'en' => 'publiq NPO',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository = new InMemoryDocumentRepository();

        $addressParser = $this->createMock(AddressParser::class);
        $addressParser->expects($this->any())
            ->method('parse')
            ->willReturn(
                new ParsedAddress(
                    'Kerkstraat',
                    '1',
                    '3271',
                    'Zichem (Scherpenheuvel-Zichem)'
                )
            );

        $logger = $this->createMock(LoggerInterface::class);

        $this->organizerJsonToTurtleConverter = new OrganizerJsonToTurtleConverter(
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/organizers/' . $item),
            $this->documentRepository,
            new OrganizerDenormalizer(),
            $addressParser,
            $logger
        );
    }

    /**
     * @test
     */
    public function it_converts_a_simple_organizer(): void
    {
        $this->givenThereIsAnOrganizer();

        $turtle = $this->organizerJsonToTurtleConverter->convert($this->organizerId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/organizer.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_a_simple_deleted_organizer(): void
    {
        $this->givenThereIsAnOrganizer(
            [
                'workflowStatus' => 'DELETED',
            ]
        );

        $turtle = $this->organizerJsonToTurtleConverter->convert($this->organizerId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/organizer-deleted.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_a_simple_organizer_without_url(): void
    {
        $organizerId = '56f1efdb-fe25-44f6-b9d7-4a6a836799d7';

        $organizer = [
            '@id' => 'https://mock.io.uitdatabank.be/organizers/' . $organizerId,
            'mainLanguage' => 'nl',
            'name' => [
                'nl' => 'publiq VZW',
                'en' => 'publiq NPO',
            ],
            'created' => '2023-01-01T12:30:15+01:00',
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($organizerId, Json::encode($organizer)));

        $turtle = $this->organizerJsonToTurtleConverter->convert($organizerId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/organizer-without-homepage.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_a_simple_organizer_without_created(): void
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
            'modified' => '2023-01-01T12:30:15+01:00',
        ];

        $this->documentRepository->save(new JsonDocument($organizerId, Json::encode($organizer)));

        $turtle = $this->organizerJsonToTurtleConverter->convert($organizerId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/organizer.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_organizer_with_address(): void
    {
        $this->givenThereIsAnOrganizer(
            [
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
            ]
        );

        $turtle = $this->organizerJsonToTurtleConverter->convert($this->organizerId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/organizer-with-address.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_organizer_with_contact_point(): void
    {
        $this->givenThereIsAnOrganizer(
            [
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
            ]
        );

        $turtle = $this->organizerJsonToTurtleConverter->convert($this->organizerId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/organizer-with-contact-point.ttl'), $turtle);
    }

    /**
     * @test
     */
    public function it_converts_an_organizer_with_labels(): void
    {
        $this->givenThereIsAnOrganizer(
            [
                'labels' => [
                    'public_label_1',
                    'public_label_2',
                ],
                'hiddenLabels' => [
                    'hidden_label_1',
                    'hidden_label_2',
                ],
            ]
        );

        $turtle = $this->organizerJsonToTurtleConverter->convert($this->organizerId);

        $this->assertEquals(file_get_contents(__DIR__ . '/ttl/organizer-with-labels.ttl'), $turtle);
    }

    private function givenThereIsAnOrganizer(array $extraProperties = []): void
    {
        $organizer = array_merge($this->organizer, $extraProperties);
        $this->documentRepository->save(new JsonDocument($this->organizerId, Json::encode($organizer)));
    }
}
