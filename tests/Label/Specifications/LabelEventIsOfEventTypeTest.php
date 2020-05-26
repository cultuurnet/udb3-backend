<?php

namespace CultuurNet\UDB3\Label\Specifications;

use CultuurNet\UDB3\Event\Events\LabelAdded as EventLabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved as EventLabelRemoved;
use CultuurNet\UDB3\Place\Events\LabelAdded as PlaceLabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved as PlaceLabelRemoved;
use PHPUnit\Framework\TestCase;

class LabelEventIsOfEventTypeTest extends TestCase
{
    /**
     * @var LabelEventIsOfEventType
     */
    private $labelEventIsOfEventType;

    protected function setUp()
    {
        $this->labelEventIsOfEventType = new LabelEventIsOfEventType();
    }

    /**
     * @test
     */
    public function it_is_satisfied_by_label_added_on_event()
    {
        $labelAdded = $this->createEvent(EventLabelAdded::class);

        $this->assertTrue($this->labelEventIsOfEventType->isSatisfiedBy(
            $labelAdded
        ));
    }

    /**
     * @test
     */
    public function it_is_satisfied_by_label_removed_from_event()
    {
        $labelRemoved = $this->createEvent(EventLabelRemoved::class);

        $this->assertTrue($this->labelEventIsOfEventType->isSatisfiedBy(
            $labelRemoved
        ));
    }

    /**
     * @test
     */
    public function it_is_not_satisfied_by_label_added_on_place()
    {
        $labelAdded = $this->createEvent(PlaceLabelAdded::class);

        $this->assertFalse($this->labelEventIsOfEventType->isSatisfiedBy(
            $labelAdded
        ));
    }

    /**
     * @test
     */
    public function it_is_not_satisfied_by_label_removed_from_place()
    {
        $labelRemoved = $this->createEvent(PlaceLabelRemoved::class);

        $this->assertFalse($this->labelEventIsOfEventType->isSatisfiedBy(
            $labelRemoved
        ));
    }

    /**
     * @param string $className
     * @return mixed
     */
    private function createEvent($className)
    {
        return $this
            ->getMockBuilder($className)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
