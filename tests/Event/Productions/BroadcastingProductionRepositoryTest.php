<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Productions;

use Broadway\EventHandling\EventBus;
use Broadway\EventHandling\TraceableEventBus;
use CultuurNet\UDB3\Event\Events\EventProjectedToJSONLD;
use CultuurNet\UDB3\Event\ReadModel\JSONLD\EventFactory;
use CultuurNet\UDB3\Iri\CallableIriGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class BroadcastingProductionRepositoryTest extends TestCase
{
    private ProductionRepository&MockObject $decoratee;

    private TraceableEventBus $eventBus;

    private BroadcastingProductionRepository $repository;

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

    /**
     * @test
     */
    public function it_should_broadcast_event_projected_to_jsonld_for_events_in_a_production_when_an_event_is_added(): void
    {
        $newEventId = '0054706e-a197-4e15-bab0-0473dc378884';

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
            ->method('addEvent')
            ->with($newEventId, $production);

        $this->repository->addEvent($newEventId, $production);

        $expected = [
            new EventProjectedToJSONLD(
                '0054706e-a197-4e15-bab0-0473dc378884',
                'https://io.uitdatabank.be/events/0054706e-a197-4e15-bab0-0473dc378884'
            ),
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

    /**
     * @test
     */
    public function it_should_broadcast_event_projected_to_jsonld_for_events_in_a_production_when_an_event_is_removed(): void
    {
        $removeEventId = 'bf1668d0-ce82-4e38-b284-2947f12850d6';

        $productionId = ProductionId::fromNative('599fc3af-0023-4c59-a0ab-05c9ad7f54cc');
        $productionAfterRemoval = new Production(
            $productionId,
            'mock production',
            [
                'd6a65aa8-d871-4a3e-a7ef-81926ad62371',
                'a40ca8ff-cdae-406c-9124-f5874ef8056a',
            ]
        );

        $this->decoratee->expects($this->once())
            ->method('removeEvent')
            ->with($removeEventId, $productionId);

        $this->decoratee->expects($this->once())
            ->method('find')
            ->with($productionId)
            ->willReturn($productionAfterRemoval);

        $this->repository->removeEvent($removeEventId, $productionId);

        $expected = [
            new EventProjectedToJSONLD(
                'bf1668d0-ce82-4e38-b284-2947f12850d6',
                'https://io.uitdatabank.be/events/bf1668d0-ce82-4e38-b284-2947f12850d6'
            ),
            new EventProjectedToJSONLD(
                'd6a65aa8-d871-4a3e-a7ef-81926ad62371',
                'https://io.uitdatabank.be/events/d6a65aa8-d871-4a3e-a7ef-81926ad62371'
            ),
            new EventProjectedToJSONLD(
                'a40ca8ff-cdae-406c-9124-f5874ef8056a',
                'https://io.uitdatabank.be/events/a40ca8ff-cdae-406c-9124-f5874ef8056a'
            ),
        ];

        $actual = $this->eventBus->getEvents();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_broadcast_event_projected_to_jsonld_for_events_in_a_production_when_multiple_events_are_removed(): void
    {
        $removeEventIds = [
            'bf1668d0-ce82-4e38-b284-2947f12850d6',
            'fc779e8a-614b-4a88-bcde-ab0825aa1443',
            '24187ed9-ae8f-41d3-88d4-09cf67608669',
        ];

        $productionId = ProductionId::fromNative('599fc3af-0023-4c59-a0ab-05c9ad7f54cc');
        $productionAfterRemoval = new Production(
            $productionId,
            'mock production',
            [
                'd6a65aa8-d871-4a3e-a7ef-81926ad62371',
                'a40ca8ff-cdae-406c-9124-f5874ef8056a',
            ]
        );

        $this->decoratee->expects($this->once())
            ->method('removeEvents')
            ->with($removeEventIds, $productionId);

        $this->decoratee->expects($this->once())
            ->method('find')
            ->with($productionId)
            ->willReturn($productionAfterRemoval);

        $this->repository->removeEvents($removeEventIds, $productionId);

        $expected = [
            new EventProjectedToJSONLD(
                'bf1668d0-ce82-4e38-b284-2947f12850d6',
                'https://io.uitdatabank.be/events/bf1668d0-ce82-4e38-b284-2947f12850d6'
            ),
            new EventProjectedToJSONLD(
                'fc779e8a-614b-4a88-bcde-ab0825aa1443',
                'https://io.uitdatabank.be/events/fc779e8a-614b-4a88-bcde-ab0825aa1443'
            ),
            new EventProjectedToJSONLD(
                '24187ed9-ae8f-41d3-88d4-09cf67608669',
                'https://io.uitdatabank.be/events/24187ed9-ae8f-41d3-88d4-09cf67608669'
            ),
            new EventProjectedToJSONLD(
                'd6a65aa8-d871-4a3e-a7ef-81926ad62371',
                'https://io.uitdatabank.be/events/d6a65aa8-d871-4a3e-a7ef-81926ad62371'
            ),
            new EventProjectedToJSONLD(
                'a40ca8ff-cdae-406c-9124-f5874ef8056a',
                'https://io.uitdatabank.be/events/a40ca8ff-cdae-406c-9124-f5874ef8056a'
            ),
        ];

        $actual = $this->eventBus->getEvents();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_should_broadcast_event_projected_to_jsonld_for_events_in_a_production_when_events_get_moved(): void
    {
        $productionIdFrom = ProductionId::fromNative('bf1668d0-ce82-4e38-b284-2947f12850d6');
        $productionIdTo = ProductionId::fromNative('06e32aa2-5b1c-4f95-b715-0cb444f520ea');

        $productionTo = new Production(
            $productionIdTo,
            'mock production 2',
            [
                'd6a65aa8-d871-4a3e-a7ef-81926ad62371',
                'a40ca8ff-cdae-406c-9124-f5874ef8056a',
            ]
        );

        $productionMerged = new Production(
            $productionIdTo,
            'mock production 2',
            [
                'd6a65aa8-d871-4a3e-a7ef-81926ad62371',
                'a40ca8ff-cdae-406c-9124-f5874ef8056a',
                '8b6dd26a-26c0-4334-8738-12f04a978d34',
                '4926608d-e396-4d06-a274-119666cf828f',
            ]
        );

        $this->decoratee->expects($this->once())
            ->method('moveEvents')
            ->with($productionIdFrom, $productionTo);

        $this->decoratee->expects($this->once())
            ->method('find')
            ->with($productionIdTo)
            ->willReturn($productionMerged);

        $this->repository->moveEvents($productionIdFrom, $productionTo);

        $expected = [
            new EventProjectedToJSONLD(
                'd6a65aa8-d871-4a3e-a7ef-81926ad62371',
                'https://io.uitdatabank.be/events/d6a65aa8-d871-4a3e-a7ef-81926ad62371'
            ),
            new EventProjectedToJSONLD(
                'a40ca8ff-cdae-406c-9124-f5874ef8056a',
                'https://io.uitdatabank.be/events/a40ca8ff-cdae-406c-9124-f5874ef8056a'
            ),
            new EventProjectedToJSONLD(
                '8b6dd26a-26c0-4334-8738-12f04a978d34',
                'https://io.uitdatabank.be/events/8b6dd26a-26c0-4334-8738-12f04a978d34'
            ),
            new EventProjectedToJSONLD(
                '4926608d-e396-4d06-a274-119666cf828f',
                'https://io.uitdatabank.be/events/4926608d-e396-4d06-a274-119666cf828f'
            ),
        ];

        $actual = $this->eventBus->getEvents();

        $this->assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function it_broadcasts_event_projected_to_jsonld_for_events_in_a_production_when_production_gets_renamed(): void
    {
        $productionId = ProductionId::fromNative('bf1668d0-ce82-4e38-b284-2947f12850d6');

        $production = new Production(
            $productionId,
            'Foo',
            [
                'd6a65aa8-d871-4a3e-a7ef-81926ad62371',
                'a40ca8ff-cdae-406c-9124-f5874ef8056a',
            ]
        );

        $this->decoratee->expects($this->once())
            ->method('renameProduction')
            ->with($productionId, 'Bar');

        $this->decoratee->expects($this->once())
            ->method('find')
            ->with($productionId)
            ->willReturn($production);

        $this->repository->renameProduction($productionId, 'Bar');

        $expected = [
            new EventProjectedToJSONLD(
                'd6a65aa8-d871-4a3e-a7ef-81926ad62371',
                'https://io.uitdatabank.be/events/d6a65aa8-d871-4a3e-a7ef-81926ad62371'
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
