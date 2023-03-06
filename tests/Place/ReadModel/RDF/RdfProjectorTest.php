<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\RDF;

use Broadway\Domain\DateTime as BroadwayDateTime;
use Broadway\Domain\DomainMessage;
use Broadway\Domain\Metadata;
use CultuurNet\UDB3\Address\Address as LegacyAddress;
use CultuurNet\UDB3\Address\Locality as LegacyLocality;
use CultuurNet\UDB3\Address\PostalCode as LegacyPostalCode;
use CultuurNet\UDB3\Address\Street as LegacyStreet;
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType as LegacyCalendarType;
use CultuurNet\UDB3\Event\EventType as LegacyEventType;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\TitleTranslated;
use CultuurNet\UDB3\Place\Events\TitleUpdated;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\InMemoryGraphRepository;
use CultuurNet\UDB3\RDF\InMemoryMainLanguageRepository;
use CultuurNet\UDB3\Title as LegacyTitle;
use DateTime;
use EasyRdf\Serialiser\Turtle;
use PHPUnit\Framework\TestCase;

class RdfProjectorTest extends TestCase
{
    private GraphRepository $graphRepository;
    private RdfProjector $rdfProjector;

    protected function setUp(): void
    {
        $this->graphRepository = new InMemoryGraphRepository();
        $this->rdfProjector = new RdfProjector(
            new InMemoryMainLanguageRepository(),
            $this->graphRepository,
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/locaties/' . $item),
        );
    }

    /**
     * @test
     */
    public function it_handles_place_created(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $this->project($placeId, [
            $this->getPlaceCreated($placeId),
        ]);
        $this->assertTurtleData($placeId, file_get_contents(__DIR__ . '/data/place-created.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_title_updated(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $this->project($placeId, [
            $this->getPlaceCreated($placeId),
            new TitleUpdated($placeId, new LegacyTitle('Voorbeeld titel UPDATED')),
        ]);
        $this->assertTurtleData($placeId, file_get_contents(__DIR__ . '/data/title-updated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_title_translated(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $this->project($placeId, [
            $this->getPlaceCreated($placeId),
            new TitleTranslated($placeId, new LegacyLanguage('en'), new LegacyTitle('Example title')),
        ]);
        $this->assertTurtleData($placeId, file_get_contents(__DIR__ . '/data/title-translated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_multiple_title_translated_and_title_updated_events(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $this->project($placeId, [
            $this->getPlaceCreated($placeId),
            new TitleTranslated($placeId, new LegacyLanguage('en'), new LegacyTitle('Example title')),
            new TitleUpdated($placeId, new LegacyTitle('Voorbeeld titel UPDATED')),
            new TitleTranslated($placeId, new LegacyLanguage('en'), new LegacyTitle('Example title UPDATED')),
            new TitleUpdated($placeId, new LegacyTitle('Voorbeeld titel UPDATED 2')),
        ]);
        $this->assertTurtleData($placeId, file_get_contents(__DIR__ . '/data/title-updated-and-translated.ttl'));
    }

    private function getPlaceCreated(string $placeId): PlaceCreated
    {
        return new PlaceCreated(
            $placeId,
            new LegacyLanguage('nl'),
            new LegacyTitle('Voorbeeld titel'),
            new LegacyEventType('0.14.0.0.0', 'Monument'),
            new LegacyAddress(
                new LegacyStreet('Martelarenlaan 1'),
                new LegacyPostalCode('3000'),
                new LegacyLocality('Leuven'),
                new CountryCode('BE')
            ),
            new Calendar(LegacyCalendarType::PERMANENT())
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
        $uri = 'https://mock.data.publiq.be/locaties/' . $placeId;
        $actualTurtleData = (new Turtle())->serialise($this->graphRepository->get($uri), 'turtle');
        $this->assertEquals(trim($expectedTurtleData), trim($actualTurtleData));
    }
}
