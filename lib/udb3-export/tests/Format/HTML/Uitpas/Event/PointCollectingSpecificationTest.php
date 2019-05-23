<?php

namespace CultuurNet\UDB3\EventExport\Format\HTML\Uitpas\Event;

use \CultureFeed_Uitpas_Event_CultureEvent as Event;

class PointCollectingSpecificationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PointCollectingSpecification
     */
    protected $specification;

    public function setUp()
    {
        $this->specification = new PointCollectingSpecification();
    }

    /**
     * @test
     * @dataProvider satisfyingEventProvider
     * @param Event $event
     */
    public function it_is_satisfied_by_events_with_points(Event $event)
    {
        $this->assertTrue($this->specification->isSatisfiedBy($event));
    }

    public function satisfyingEventProvider()
    {
        $factory = new EventFactory();
        return [
            [
                $factory->buildEventWithPoints(0.01),
            ],
            [
                $factory->buildEventWithPoints(0.2),
            ],
            [
                $factory->buildEventWithPoints(3.00),
            ],
            [
                $factory->buildEventWithPoints(4),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider unsatisfyingEventProvider
     * @param Event $event
     */
    public function it_is_unsatisfied_by_events_without_points(Event $event)
    {
        $this->assertFalse($this->specification->isSatisfiedBy($event));
    }

    public function unsatisfyingEventProvider()
    {
        $factory = new EventFactory();
        return [
            [
                $factory->buildEventWithPoints(0),
            ],
            [
                $factory->buildEventWithPoints(0.00),
            ],
            [
                $factory->buildEventWithPoints(-1),
            ],
            [
                $factory->buildEventWithPoints(-1.00),
            ],
            [
                new Event(),
            ],
        ];
    }
}
