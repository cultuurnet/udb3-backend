<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\ReadModel\JSONLD;

use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Iri\IriGeneratorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EventFactoryTest extends TestCase
{
    /**
     * @var IriGeneratorInterface&MockObject
     */
    private $iriGenerator;

    private EventFactory $factory;

    public function setUp(): void
    {
        $this->iriGenerator = $this->createMock(IriGeneratorInterface::class);

        $this->factory = new EventFactory(
            $this->iriGenerator
        );
    }

    /**
     * @test
     */
    public function it_adds_an_iri_based_on_the_id_when_creating_the_event(): void
    {
        $id = '1';
        $iri = 'event/1';
        $expectedEvent = new EventProjectedToJSONLD($id, $iri);

        $this->iriGenerator->expects($this->once())
            ->method('iri')
            ->with($id)
            ->willReturn($iri);

        $actualEvent = $this->factory->createEvent($id);

        $this->assertEquals($expectedEvent, $actualEvent);
    }
}
