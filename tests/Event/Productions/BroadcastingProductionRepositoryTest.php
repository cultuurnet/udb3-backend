<?php

namespace CultuurNet\UDB3\Event\Productions;

use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\TraceableEventBus;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\EventFactory;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BroadcastingProductionRepositoryTest extends TestCase
{
    /**
     * @var ProductionRepository|MockObject
     */
    private $decoratee;

    /**
     * @var TraceableEventBus
     */
    private $eventBus;

    /**
     * @var BroadcastingProductionRepository
     */
    private $repository;

    protected function setUp(): void
    {
        $this->decoratee = $this->createMock(ProductionRepository::class);
        $this->eventBus = new TraceableEventBus($this->createMock(EventBus::class));
        $this->repository = new BroadcastingProductionRepository(
            $this->decoratee,
            $this->eventBus,
            new EventFactory(
                new CallableIriGenerator(
                    function (string $eventId): string {
                        return 'https://io.uitdatabank.be/events/' . $eventId;
                    }
                )
            )
        );

        $this->eventBus->trace();
    }

    /**
     * @test
     */
    public function it_should_broadcast_event_projected_to_jsonld_for_events_in_a_new_production(): void
    {
        $production = new Production(
            ProductionId::fromNative('599fc3af-0023-4c59-a0ab-05c9ad7f54cc'),
            'mock production',
            [
                'd6a65aa8-d871-4a3e-a7ef-81926ad62371',
                'bf1668d0-ce82-4e38-b284-2947f12850d6',
                'a40ca8ff-cdae-406c-9124-f5874ef8056a',
            ]
        );

        $this->decoratee->expects($this->once())
            ->method('add')
            ->with($production);

        $this->repository->add($production);

        $expected = [
            new EventProjectedToJSONLD(
                'd6a65aa8-d871-4a3e-a7ef-81926ad62371',
                'https://io.uitdatabank.be/events/d6a65aa8-d871-4a3e-a7ef-81926ad62371'
            ),
            new EventProjectedToJSONLD(
                'bf1668d0-ce82-4e38-b284-2947f12850d6',
                'https://io.uitdatabank.be/events/bf1668d0-ce82-4e38-b284-2947f12850d6'
            ),
            new EventProjectedToJSONLD(
                'a40ca8ff-cdae-406c-9124-f5874ef8056a',
                'https://io.uitdatabank.be/events/a40ca8ff-cdae-406c-9124-f5874ef8056a'
            ),
        ];

        $actual = $this->eventBus->getEvents();

        $this->assertEquals($expected, $actual);
    }
}
