<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Place\ReadModel\RDF;

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
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\TitleUpdated;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\InMemoryGraphRepository;
use CultuurNet\UDB3\RDF\InMemoryMainLanguageRepository;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
use CultuurNet\UDB3\Title;
use CultuurNet\UDB3\Title as LegacyTitle;
use EasyRdf\Serialiser\Turtle;
use PHPUnit\Framework\TestCase;

class RdfProjectorTest extends TestCase
{
    private MainLanguageRepository $mainLanguageRepository;
    private GraphRepository $graphRepository;
    private RdfProjector $rdfProjector;

    protected function setUp(): void
    {
        $this->mainLanguageRepository = new InMemoryMainLanguageRepository();
        $this->graphRepository = new InMemoryGraphRepository();
        $this->rdfProjector = new RdfProjector(
            $this->mainLanguageRepository,
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
        $this->projectPlaceCreated($placeId);

        $expectedUri = 'https://mock.data.publiq.be/locaties/' . $placeId;
        $expectedMainLanguage = new Language('nl');
        $actualMainLanguage = $this->mainLanguageRepository->get($expectedUri);

        $expectedTurtle = file_get_contents(__DIR__ . '/data/place-created.ttl');
        $actualTurtle = $this->getTurtleData($placeId);

        $this->assertEquals($expectedMainLanguage, $actualMainLanguage);
        $this->assertEquals($expectedTurtle, $actualTurtle);
    }

    /**
     * @test
     */
    public function it_handles_title_updated(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $this->projectPlaceCreated($placeId);

        $titleUpdated = new TitleUpdated($placeId, new Title('Voorbeeld titel UPDATED'));
        $this->handleEvent($placeId, $titleUpdated);

        $expectedTurtle = file_get_contents(__DIR__ . '/data/title-updated.ttl');
        $actualTurtle = $this->getTurtleData($placeId);

        $this->assertEquals($expectedTurtle, $actualTurtle);
    }

    private function projectPlaceCreated(string $placeId): void
    {
        $placeCreated = new PlaceCreated(
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
        $this->handleEvent($placeId, $placeCreated);
    }

    private function handleEvent(string $placeId, $event): void
    {
        $domainMessage = DomainMessage::recordNow($placeId, 1, new Metadata(), $event);
        $this->rdfProjector->handle($domainMessage);
    }

    private function getTurtleData(string $placeId): string
    {
        $uri = 'https://mock.data.publiq.be/locaties/' . $placeId;
        return (new Turtle())->serialise($this->graphRepository->get($uri), 'turtle');
    }
}
