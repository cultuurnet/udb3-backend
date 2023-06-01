<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Model\Serializer\Event\EventDenormalizer;
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

    protected function setUp(): void
    {
        $this->graphRepository = new InMemoryGraphRepository();
        $this->documentRepository = new InMemoryDocumentRepository();

        $this->rdfProjector = new RdfProjector(
            $this->graphRepository,
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/events/' . $item),
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/places/' . $item),
            new CallableIriGenerator(fn (string $item): string => 'https://mock.taxonomy.uitdatabank.be/terms/' . $item),
            $this->documentRepository,
            new EventDenormalizer()
        );
    }

    /**
     * @test
     */
    public function it_converts_a_simple_event(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_a_description(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'description' => [
                'nl' => 'Dit is het laatste concert van Faith no more',
            ],
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-description.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_translations(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'permanent',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
                'fr' => 'Foi non plus',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
            'description' => [
                'nl' => 'Dit is het laatste concert van Faith no more',
                'fr' => 'Ceci est le dernier concert de Foi non plus',
                'en' => 'This is the last concert of Faith no more',
            ],
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-translations.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_periodic_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'periodic',
            'startDate' => '2020-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-calendar-periodic.ttl'));
    }

    /**
     * @test
     */
    public function it_converts_an_event_with_single_calendar(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $event = [
            '@id' => 'https://mock.io.uitdatabank.be/events/' . $eventId,
            'mainLanguage' => 'nl',
            'calendarType' => 'single',
            'startDate' => '2023-05-06T20:00:00+01:00',
            'endDate' => '2023-05-06T23:00:00+01:00',
            'terms' => [
                [
                    'id' => '0.50.4.0.0',
                    'domain' => 'eventtype',
                ],
            ],
            'name' => [
                'nl' => 'Faith no more',
            ],
            'location' => [
                '@id' => 'https://mock.io.uitdatabank.be/places/bfc60a14-6208-4372-942e-86e63744769a',
            ],
        ];

        $this->documentRepository->save(new JsonDocument($eventId, json_encode($event)));

        $this->project(
            $eventId,
            [
                new EventProjectedToJSONLD($eventId, 'https://mock.io.uitdatabank.be/events/' . $eventId),
            ]
        );

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/ttl/event-with-calendar-single.ttl'));
    }

    private function project(string $eventId, array $events): void
    {
        $playhead = -1;
        $recordedOn = new DateTime('2022-12-31T12:30:15+01:00');
        foreach ($events as $event) {
            $playhead++;
            $recordedOn->modify('+1 day');
            $domainMessage = new DomainMessage(
                $eventId,
                $playhead,
                new Metadata(),
                $event,
                BroadwayDateTime::fromString($recordedOn->format(DateTime::ATOM))
            );
            $this->rdfProjector->handle($domainMessage);
        }
    }

    private function assertTurtleData(string $eventId, string $expectedTurtleData): void
    {
        $uri = 'https://mock.data.publiq.be/events/' . $eventId;
        $actualTurtleData = (new Turtle())->serialise($this->graphRepository->get($uri), 'turtle');
        $this->assertEquals(trim($expectedTurtleData), trim($actualTurtleData));
    }
}
