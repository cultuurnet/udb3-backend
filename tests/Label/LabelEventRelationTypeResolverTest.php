<?php

namespace CultuurNet\UDB3\Label;

use CultuurNet\UDB3\Event\Events\LabelAdded as EventLabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved as EventLabelRemoved;
use CultuurNet\UDB3\Label\ValueObjects\RelationType;
use CultuurNet\UDB3\Organizer\Events\LabelAdded as OrganizerLabelAdded;
use CultuurNet\UDB3\Organizer\Events\LabelRemoved as OrganizerLabelRemoved;
use CultuurNet\UDB3\Place\Events\LabelAdded as PlaceLabelAdded;
use CultuurNet\UDB3\Place\Events\LabelRemoved as PlaceLabelRemoved;
use PHPUnit\Framework\TestCase;

class LabelEventRelationTypeResolverTest extends TestCase
{
    /**
     * @var LabelEventRelationTypeResolver
     */
    private $labelEventRelationTypeResolver;

    protected function setUp()
    {
        $this->labelEventRelationTypeResolver = new LabelEventRelationTypeResolver();
    }

    /**
     * @test
     */
    public function it_returns_relation_type_event_for_label_added_on_event()
    {
        $labelAdded = $this->createEvent(EventLabelAdded::class);

        $this->assertEquals(
            RelationType::EVENT(),
            $this->labelEventRelationTypeResolver->getRelationType($labelAdded)
        );
    }

    /**
     * @test
     */
    public function it_returns_relation_type_event_for_label_removed_from_event()
    {
        $labelRemoved = $this->createEvent(EventLabelRemoved::class);

        $this->assertEquals(
            RelationType::EVENT(),
            $this->labelEventRelationTypeResolver->getRelationType($labelRemoved)
        );
    }

    /**
     * @test
     */
    public function it_returns_relation_type_place_for_label_added_on_place()
    {
        $labelAdded = $this->createEvent(PlaceLabelAdded::class);

        $this->assertEquals(
            RelationType::PLACE(),
            $this->labelEventRelationTypeResolver->getRelationType($labelAdded)
        );
    }

    /**
     * @test
     */
    public function it_returns_relation_type_place_for_label_removed_from_place()
    {
        $labelRemoved = $this->createEvent(PlaceLabelRemoved::class);

        $this->assertEquals(
            RelationType::PLACE(),
            $this->labelEventRelationTypeResolver->getRelationType($labelRemoved)
        );
    }

    /**
     * @test
     */
    public function it_returns_relation_type_organizer_for_label_added_on_organizer()
    {
        $labelAdded = $this->createEvent(OrganizerLabelAdded::class);

        $this->assertEquals(
            RelationType::ORGANIZER(),
            $this->labelEventRelationTypeResolver->getRelationType($labelAdded)
        );
    }

    /**
     * @test
     */
    public function it_returns_relation_type_organizer_for_label_removed_from_organizer()
    {
        $labelRemoved = $this->createEvent(OrganizerLabelRemoved::class);

        $this->assertEquals(
            RelationType::ORGANIZER(),
            $this->labelEventRelationTypeResolver->getRelationType($labelRemoved)
        );
    }

    /**
     * @test
     */
    public function it_throws_illegal_argument_for_label_events_other_then_added_or_removed()
    {
        $this->expectException(\InvalidArgumentException::class);

        $dummyLabelEvent = $this->createEvent(DummyLabelEvent::class);
        $this->labelEventRelationTypeResolver->getRelationType($dummyLabelEvent);
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
