<?php

namespace CultuurNet\UDB3\Label\Specifications;

use CultuurNet\UDB3\Organizer\Events\LabelAdded as OrganizerLabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved as OrganizerLabelRemoved;
use CultuurNet\UDB3\Place\Events\LabelAdded as PlaceLabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved as PlaceLabelRemoved;
use PHPUnit\Framework\TestCase;

class LabelEventIsOfOrganizerTypeTest extends TestCase
{
    /**
     * @var LabelEventIsOfOrganizerType
     */
    private $labelEventIsOfOrganizerType;

    protected function setUp()
    {
        $this->labelEventIsOfOrganizerType = new LabelEventIsOfOrganizerType();
    }

    /**
     * @test
     */
    public function it_is_satisfied_by_label_added_on_event()
    {
        $labelAdded = $this->createEvent(OrganizerLabelAdded::class);

        $this->assertTrue($this->labelEventIsOfOrganizerType->isSatisfiedBy(
            $labelAdded
        ));
    }

    /**
     * @test
     */
    public function it_is_satisfied_by_label_removed_from_event()
    {
        $labelRemoved = $this->createEvent(OrganizerLabelRemoved::class);

        $this->assertTrue($this->labelEventIsOfOrganizerType->isSatisfiedBy(
            $labelRemoved
        ));
    }

    /**
     * @test
     */
    public function it_is_not_satisfied_by_label_added_on_place()
    {
        $labelAdded = $this->createEvent(PlaceLabelAdded::class);

        $this->assertFalse($this->labelEventIsOfOrganizerType->isSatisfiedBy(
            $labelAdded
        ));
    }

    /**
     * @test
     */
    public function it_is_not_satisfied_by_label_removed_from_place()
    {
        $labelRemoved = $this->createEvent(PlaceLabelRemoved::class);

        $this->assertFalse($this->labelEventIsOfOrganizerType->isSatisfiedBy(
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
