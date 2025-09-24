<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event;

use CultureFeed_Uitpas_Event_CultureEvent as Event;
use PHPUnit\Framework\TestCase;

class PointCollectingSpecificationTest extends TestCase
{
    protected PointCollectingSpecification $specification;

    public function setUp(): void
    {
        $this->specification = new PointCollectingSpecification();
    }

    /**
     * @test
     * @dataProvider satisfyingEventProvider
     */
    public function it_is_satisfied_by_events_with_points(Event $event): void
    {
        $this->assertTrue($this->specification->isSatisfiedBy($event));
    }

    public function satisfyingEventProvider(): array
    {
        $factory = new EventFactory();
        return [
            [
                $factory->buildEventWithPoints(1),
            ],
            [
                $factory->buildEventWithPoints(20),
            ],
            [
                $factory->buildEventWithPoints(300),
            ],
            [
                $factory->buildEventWithPoints(4),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider unsatisfyingEventProvider
     */
    public function it_is_unsatisfied_by_events_without_points(Event $event): void
    {
        $this->assertFalse($this->specification->isSatisfiedBy($event));
    }

    public function unsatisfyingEventProvider(): array
    {
        $factory = new EventFactory();
        return [
            'zero' => [
                $factory->buildEventWithPoints(0),
            ],
            'negative' => [
                $factory->buildEventWithPoints(-1),
            ],
            'uninitialized' => [
                new Event(),
            ],
        ];
    }
}
