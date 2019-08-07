<?php

namespace CultuurNet\UDB3\UDB2\Actor;

use Broadway\Domain\AggregateRoot;
use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Place\Events\PlaceImportedFromUDB2;
use CultuurNet\UDB3\Place\Place;

class ActorToUDB3PlaceFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function it_creates_a_place_entity_based_on_cdbxml()
    {
        $factory = new ActorToUDB3PlaceFactory();

        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $cdbXml = file_get_contents(__DIR__ . '/samples/actor.xml');
        $cdbXmlNamespaceUri = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

        $place = $factory->createFromCdbXml(
            $id,
            $cdbXml,
            $cdbXmlNamespaceUri
        );

        $this->assertInstanceOf(Place::class, $place);
        $this->assertEvents(
            [
                new PlaceImportedFromUDB2(
                    $id,
                    $cdbXml,
                    $cdbXmlNamespaceUri
                ),
            ],
            $place
        );
    }

    private function assertEvents(array $expectedEvents, AggregateRoot $place)
    {
        $domainMessages = iterator_to_array(
            $place->getUncommittedEvents()->getIterator()
        );

        $payloads = array_map(
            function (DomainMessage $item) {
                return $item->getPayload();
            },
            $domainMessages
        );

        $this->assertEquals(
            $expectedEvents,
            $payloads
        );
    }
}
