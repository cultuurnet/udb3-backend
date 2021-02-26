<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label\Specifications;

use CultuurNet\UDB3\Event\Events\LabelAdded as EventLabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved as EventLabelRemoved;
use CultuurNet\UDB3\Label as LabelValueObject;
use CultuurNet\UDB3\Place\Events\LabelAdded as PlaceLabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved as PlaceLabelRemoved;
use PHPUnit\Framework\TestCase;

class LabelEventIsOfPlaceTypeTest extends TestCase
{
    /**
     * @var LabelEventIsOfPlaceType
     */
    private $labelEventIsOfPlaceType;

    protected function setUp(): void
    {
        $this->labelEventIsOfPlaceType = new LabelEventIsOfPlaceType();
    }

    /**
     * @test
     */
    public function it_is_satisfied_by_label_added_on_place(): void
    {
        $labelAdded = new PlaceLabelAdded('6b96a237-2e00-49a2-ba6d-fc2beab0707e', new LabelValueObject('foo'));

        $this->assertTrue($this->labelEventIsOfPlaceType->isSatisfiedBy(
            $labelAdded
        ));
    }

    /**
     * @test
     */
    public function it_is_satisfied_by_label_removed_from_place(): void
    {
        $labelRemoved = new PlaceLabelRemoved('6b96a237-2e00-49a2-ba6d-fc2beab0707e', new LabelValueObject('foo'));

        $this->assertTrue($this->labelEventIsOfPlaceType->isSatisfiedBy(
            $labelRemoved
        ));
    }

    /**
     * @test
     */
    public function it_is_not_satisfied_by_label_added_on_event(): void
    {
        $labelAdded = new EventLabelAdded('6b96a237-2e00-49a2-ba6d-fc2beab0707e', new LabelValueObject('foo'));

        $this->assertFalse($this->labelEventIsOfPlaceType->isSatisfiedBy(
            $labelAdded
        ));
    }

    /**
     * @test
     */
    public function it_is_not_satisfied_by_label_deleted_from_event(): void
    {
        $labelRemoved = new EventLabelRemoved('6b96a237-2e00-49a2-ba6d-fc2beab0707e', new LabelValueObject('foo'));

        $this->assertFalse($this->labelEventIsOfPlaceType->isSatisfiedBy(
            $labelRemoved
        ));
    }
}
