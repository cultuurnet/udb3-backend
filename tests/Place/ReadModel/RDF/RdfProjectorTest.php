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
use CultuurNet\UDB3\Language as LegacyLanguage;
use CultuurNet\UDB3\Model\ValueObject\Geography\CountryCode;
use CultuurNet\UDB3\Model\ValueObject\Translation\Language;
use CultuurNet\UDB3\Place\Events\PlaceCreated;
use CultuurNet\UDB3\RDF\GraphRepository;
use CultuurNet\UDB3\RDF\InMemoryGraphRepository;
use CultuurNet\UDB3\RDF\InMemoryMainLanguageRepository;
use CultuurNet\UDB3\RDF\MainLanguageRepository;
use CultuurNet\UDB3\Title as LegacyTitle;
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
            $this->graphRepository
        );
    }

    /**
     * @test
     */
    public function it_handles_place_created(): void
    {
        $placeId = 'd4b46fba-6433-4f86-bcb5-edeef6689fea';
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
        $domainMessage = DomainMessage::recordNow($placeId, 0, new Metadata(), $placeCreated);

        $this->rdfProjector->handle($domainMessage);

        $expected = new Language('nl');
        $actual = $this->mainLanguageRepository->get($placeId);

        $this->assertEquals($expected, $actual);
    }
}
