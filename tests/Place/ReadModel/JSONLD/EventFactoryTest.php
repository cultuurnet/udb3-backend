<?php

namespace CultuurNet\UDB3\Place\ReadModel\JSONLD;

use CultuurNet\UDB3\Place\Events\PlaceProjectedToJSONLD;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventFactoryTest extends TestCase
{
    /**
     * @var IriGeneratorInterface|MockObject
     */
    private $iriGenerator;

    /**
     * @var EventFactory
     */
    private $factory;

    public function setUp()
    {
        $this->iriGenerator = $this->createMock(IriGeneratorInterface::class);

        $this->factory = new EventFactory(
            $this->iriGenerator
        );
    }

    /**
     * @test
     */
    public function it_adds_an_iri_based_on_the_id_when_creating_the_event()
    {
        $id = '1';
        $iri = 'place/1';
        $expectedEvent = new PlaceProjectedToJSONLD($id, $iri);

        $this->iriGenerator->expects($this->once())
            ->method('iri')
            ->with($id)
            ->willReturn($iri);

        $actualEvent = $this->factory->createEvent($id);

        $this->assertEquals($expectedEvent, $actualEvent);
    }
}
