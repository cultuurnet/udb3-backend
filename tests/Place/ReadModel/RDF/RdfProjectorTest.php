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
use CultuurNet\UDB3\Calendar;
use CultuurNet\UDB3\CalendarType as LegacyCalendarType;
use CultuurNet\UDB3\Event\EventType as LegacyEventType;
use CultuurNet\UDB3\Geocoding\Coordinate\Coordinates;
use CultuurNet\UDB3\Geocoding\Coordinate\Latitude;
use CultuurNet\UDB3\Geocoding\Coordinate\Longitude;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Place\Events\AddressTranslated;
use CultuurNet\UDB3\Place\Events\AddressUpdated;
use CultuurNet\UDB3\Place\Events\GeoCoordinatesUpdated;
use CultuurNet\UDB3\Place\Events\Moderation\Approved;
use CultuurNet\UDB3\Place\Events\Moderation\Published;
use CultuurNet\UDB3\Place\Events\Moderation\Rejected;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\Place\Events\TitleTranslated;
use CultuurNet\UDB3\Place\Events\TitleUpdated;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\InMemoryGraphRepository;
use CultuurNet\UDB3\RDF\InMemoryMainLanguageRepository;
use CultuurNet\UDB3\StringLiteral;
use CultuurNet\UDB3\Title as LegacyTitle;
use DateTime;
use EasyRdf\Serialiser\Turtle;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RdfProjectorTest extends TestCase
{
    private GraphRepository $graphRepository;
    /** @var AddressParser&MockObject */
    private AddressParser $addressParser;
    private RdfProjector $rdfProjector;
    private array $expectedParsedAddresses;

    private LegacyAddress $defaultAddress;

    protected function setUp(): void
    {
        $this->graphRepository = new InMemoryGraphRepository();
        $this->addressParser = $this->createMock(AddressParser::class);
        $this->rdfProjector = new RdfProjector(
            new InMemoryMainLanguageRepository(),
            $this->graphRepository,
            new CallableIriGenerator(fn (string $item): string => 'https://mock.data.publiq.be/places/' . $item),
            $this->addressParser
        );

        $this->addressParser->expects($this->any())
            ->method('parse')
            ->willReturnCallback(
                fn (string $formatted): ?ParsedAddress => $this->expectedParsedAddresses[$formatted] ?? null
            );
        $this->expectedParsedAddresses = [];

        $this->defaultAddress = new LegacyAddress(
            new LegacyStreet('Martelarenlaan 1'),
            new LegacyPostalCode('3000'),
            new LegacyLocality('Leuven'),
            new CountryCode('BE')
        );

        $this->expectParsedAddress(
            $this->defaultAddress,
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

    /**
     * @test
     */
    public function it_handles_address_updated(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $this->project($placeId, [
            $this->getPlaceCreated($placeId),
            new AddressUpdated(
                $placeId,
                new LegacyAddress(
                    new LegacyStreet('Martelarenlaan 2'),
                    new LegacyPostalCode('3002'),
                    new LegacyLocality('Amsterdam'),
                    new CountryCode('NL')
                )
            ),
        ]);
        $this->assertTurtleData($placeId, file_get_contents(__DIR__ . '/data/address-updated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_address_translated(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $translatedAddress = new LegacyAddress(
            new LegacyStreet('Martelarenlaan 1'),
            new LegacyPostalCode('3000'),
            new LegacyLocality('Louvain'),
            new CountryCode('BE')
        );
        $parsedTranslatedAddress = new ParsedAddress(
            'Martelarenlaan',
            '1',
            '3000',
            'Louvain'
        );
        $this->expectParsedAddress($translatedAddress, $parsedTranslatedAddress);

        $this->project($placeId, [
            $this->getPlaceCreated($placeId),
            new AddressTranslated(
                $placeId,
                $translatedAddress,
                new LegacyLanguage('fr')
            ),
        ]);
        $this->assertTurtleData($placeId, file_get_contents(__DIR__ . '/data/address-translated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_geo_coordinates_updated(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $this->project($placeId, [
            $this->getPlaceCreated($placeId),
            new GeoCoordinatesUpdated(
                $placeId,
                new Coordinates(
                    new Latitude(50.879),
                    new Longitude(4.6997)
                )
            ),
        ]);
        $this->assertTurtleData($placeId, file_get_contents(__DIR__ . '/data/geo-coordinates-updated.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_published(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $this->project($placeId, [
            $this->getPlaceCreated($placeId),
            new Published($placeId, new DateTime('2023-04-23T12:30:15+02:00')),
        ]);
        $this->assertTurtleData($placeId, file_get_contents(__DIR__ . '/data/place-published.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_approved(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $this->project($placeId, [
            $this->getPlaceCreated($placeId),
            new Approved($placeId),
        ]);
        $this->assertTurtleData($placeId, file_get_contents(__DIR__ . '/data/place-approved.ttl'));
    }

    /**
     * @test
     */
    public function it_handles_rejected(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
        $this->project($placeId, [
            $this->getPlaceCreated($placeId),
            new Rejected($placeId, new StringLiteral('Not good enough!')),
        ]);
        $this->assertTurtleData($placeId, file_get_contents(__DIR__ . '/data/place-rejected.ttl'));
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
