<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\InMemoryGraphRepository;
use CultuurNet\UDB3\RDF\InMemoryMainLanguageRepository;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Title;
use DateTime;
use EasyRdf\Serialiser\Turtle;
use PHPUnit\Framework\TestCase;

class RdfProjectorTest extends TestCase
{
    private RdfProjector $rdfProjector;

    private GraphRepository $graphRepository;

    protected function setUp(): void
    {
        $this->graphRepository = new InMemoryGraphRepository();

        $this->rdfProjector = new RdfProjector(
            new InMemoryMainLanguageRepository(),
            $this->graphRepository,
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/events/' . $item),
        );
    }

    /**
     * @test
     */
    public function it_handles_title_updated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new TitleUpdated($eventId, new Title('Faith no more in concert')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/title-updated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_title_translated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new TitleTranslated($eventId, new Language('de'), new Title('Faith no more im Konzert')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/title-translated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_multiple_title_translated_and_title_updated_events(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new TitleTranslated($eventId, new Language('de'), new Title('Faith no more im Konzert')),
            new TitleUpdated($eventId, new Title('Faith no more im concert')),
            new TitleTranslated($eventId, new Language('de'), new Title('Faith no more im Konzert [UPDATED]')),
            new TitleUpdated($eventId, new Title('Faith no more in concert [UPDATED]')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/title-updated-and-translated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_published(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new Published($eventId, new DateTime('2023-04-23T12:30:15+02:00')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/published.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_approved(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new Approved($eventId),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/approved.ttl'));
    }

    private function getEventCreated(string $eventId): EventCreated
    {
        return new EventCreated(
            $eventId,
            new Language('nl'),
            new Title('Faith no more'),
            new EventType('0.50.4.0.0', 'Concert'),
            new LocationId('bfc60a14-6208-4372-942e-86e63744769a'),
            new Calendar(CalendarType::PERMANENT()),
            new Theme('1.8.1.0.0', 'Rock')
        );
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
        $uri = 'https://mock.data.publiq.be/events/' . $placeId;
        $actualTurtleData = (new Turtle())->serialise($this->graphRepository->get($uri), 'turtle');
        $this->assertEquals(trim($expectedTurtleData), trim($actualTurtleData));
    }
}
