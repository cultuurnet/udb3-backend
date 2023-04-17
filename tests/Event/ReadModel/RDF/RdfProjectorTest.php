<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\RDF;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType;
use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Event\Events\CalendarUpdated;
use CultuurNet\UDB3\Event\Events\DescriptionTranslated;
use CultuurNet\UDB3\Event\Events\DescriptionUpdated;
use CultuurNet\UDB3\Event\Events\EventCreated;
use CultuurNet\UDB3\Event\Events\EventDeleted;
use CultuurNet\UDB3\Event\Events\LocationUpdated;
use CultuurNet\UDB3\Event\Events\Moderation\Approved;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsDuplicate;
use CultuurNet\UDB3\Event\Events\Moderation\FlaggedAsInappropriate;
use CultuurNet\UDB3\Event\Events\Moderation\Published;
use CultuurNet\UDB3\Event\Events\Moderation\Rejected;
use CultuurNet\UDB3\Event\Events\TitleTranslated;
use CultuurNet\UDB3\Event\Events\TitleUpdated;
use CultuurNet\UDB3\Event\EventType;
use CultuurNet\UDB3\Event\ValueObjects\LocationId;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Language;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\InMemoryGraphRepository;
use CultuurNet\UDB3\RDF\InMemoryMainLanguageRepository;
use CultuurNet\UDB3\StringLiteral;
use CultuurNet\UDB3\Theme;
use CultuurNet\UDB3\Timestamp;
use CultuurNet\UDB3\Title;
use DateTime;
use DateTimeImmutable;
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
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/places/' . $item),
        );
    }

    /**
     * @test
     */
    public function it_handles_event_created(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/created.ttl'));
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

    /**
     * @test
     */
    public function it_handles_rejected(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new Rejected($eventId, new StringLiteral('This is not a valid event')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/rejected.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_flagged_as_duplicate(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new FlaggedAsDuplicate($eventId),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/rejected.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_flagged_as_inappropriate(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new FlaggedAsInappropriate($eventId),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/rejected.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_deleted(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new EventDeleted($eventId),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/deleted.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_description_updated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new DescriptionUpdated($eventId, new Description('Dit is het laatste concert van Faith no more')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/description-updated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_description_translated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new DescriptionUpdated($eventId, new Description('Dit is het laatste concert van Faith no more')),
            new DescriptionTranslated($eventId, new Language('en'), new Description('This will be the last concert of Faith no more')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/description-translated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_location_updated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new LocationUpdated($eventId, new LocationId('ee4300a6-82a0-4489-ada0-1a6be1fca442')),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/location-updated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_calendar_updated_to_single(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $startDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00');
        $endDate = DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00');

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new CalendarUpdated(
                $eventId,
                new Calendar(
                    CalendarType::SINGLE(),
                    $startDate,
                    $endDate,
                    [
                        new Timestamp($startDate, $endDate),
                    ]
                ),
            ),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/calendar-updated-single.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_calendar_updated_to_multiple(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new CalendarUpdated(
                $eventId,
                new Calendar(
                    CalendarType::MULTIPLE(),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2020-01-02T17:00:00+01:00'),
                    [
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00')
                        ),
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T17:00:00+01:00')
                        ),
                    ]
                ),
            ),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/calendar-updated-multiple.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_various_calendar_updated(): void
    {
        $eventId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';

        $this->project($eventId, [
            $this->getEventCreated($eventId),
            new CalendarUpdated(
                $eventId,
                new Calendar(
                    CalendarType::MULTIPLE(),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T12:00:00+01:00'),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2020-01-03T17:00:00+01:00'),
                    [
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-02T17:00:00+01:00')
                        ),
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-03T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-03T17:00:00+01:00')
                        ),
                    ]
                ),
            ),
            new CalendarUpdated(
                $eventId,
                new Calendar(
                    CalendarType::SINGLE(),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                    DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00'),
                    [
                        new Timestamp(
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T12:00:00+01:00'),
                            DateTimeImmutable::createFromFormat(\DATE_ATOM, '2018-01-01T17:00:00+01:00')
                        ),
                    ]
                ),
            ),
        ]);

        $this->assertTurtleData($eventId, file_get_contents(__DIR__ . '/data/various-calendar-updated.ttl'));
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
