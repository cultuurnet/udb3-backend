<?php

declare(strict_types=1);
/**
 * @file
 */

namespace CultuurNet\UDB3\UDB2\Actor;

use Broadway\Domain\AggregateRoot;
use Broadway\Domain\DomainMessage;
use CultuurNet\UDB3\Organizer\Events\OrganizerImportedFromUDB2;
use CultuurNet\UDB3\Organizer\Organizer;
use PHPUnit\Framework\TestCase;

class ActorToUDB3OrganizerFactoryTest extends TestCase
{
    /**
     * @test
     */
    public function it_creates_an_organizer_entity_based_on_cdbxml()
    {
        $factory = new ActorToUDB3OrganizerFactory();

        $id = '404EE8DE-E828-9C07-FE7D12DC4EB24480';
        $cdbXml = file_get_contents(__DIR__ . '/samples/actor.xml');
        $cdbXmlNamespaceUri = 'http://www.cultuurdatabank.com/XMLSchema/CdbXSD/3.3/FINAL';

        $organizer = $factory->createFromCdbXml(
            $id,
            $cdbXml,
            $cdbXmlNamespaceUri
        );

        $this->assertInstanceOf(Organizer::class, $organizer);
        $this->assertEvents(
            [
                new OrganizerImportedFromUDB2(
                    $id,
                    $cdbXml,
                    $cdbXmlNamespaceUri
                ),
            ],
            $organizer
        );
    }

    private function assertEvents(array $expectedEvents, AggregateRoot $organizer)
    {
        $domainMessages = iterator_to_array(
            $organizer->getUncommittedEvents()->getIterator()
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
