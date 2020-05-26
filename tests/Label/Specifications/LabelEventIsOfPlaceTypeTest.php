<?php

namespace CultuurNet\UDB3\Label\Specifications;

use CultuurNet\UDB3\Event\Events\LabelAdded as EventLabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved as EventLabelRemoved;
use CultuurNet\UDB3\Place\Events\LabelAdded as PlaceLabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved as PlaceLabelRemoved;
use PHPUnit\Framework\TestCase;

class LabelEventIsOfPlaceTypeTest extends TestCase
{
    /**
     * @var LabelEventIsOfPlaceType
     */
    private $labelEventIsOfPlaceType;

    protected function setUp()
    {
        $this->labelEventIsOfPlaceType = new LabelEventIsOfPlaceType();
    }

    /**
     * @test
     */
    public function it_is_satisfied_by_label_added_on_place()
    {
        $labelAdded = $this->createEvent(PlaceLabelAdded::class);

        $this->assertTrue($this->labelEventIsOfPlaceType->isSatisfiedBy(
            $labelAdded
        ));
    }

    /**
     * @test
     */
    public function it_is_satisfied_by_label_removed_from_place()
    {
        $labelRemoved = $this->createEvent(PlaceLabelRemoved::class);

        $this->assertTrue($this->labelEventIsOfPlaceType->isSatisfiedBy(
            $labelRemoved
        ));
    }

    /**
     * @test
     */
    public function it_is_not_satisfied_by_label_added_on_event()
    {
        $labelAdded = $this->createEvent(EventLabelAdded::class);

        $this->assertFalse($this->labelEventIsOfPlaceType->isSatisfiedBy(
            $labelAdded
        ));
    }

    /**
     * @test
     */
    public function it_is_not_satisfied_by_label_deleted_from_event()
    {
        $labelRemoved = $this->createEvent(EventLabelRemoved::class);

        $this->assertFalse($this->labelEventIsOfPlaceType->isSatisfiedBy(
            $labelRemoved
        ));
    }


    /**
     * @param string $className
     * @return mixed
     */
    private function createEvent($className)
    {
        return $this->createMock($className);
    }
}
