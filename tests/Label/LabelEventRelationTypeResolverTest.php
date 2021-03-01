<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Label;

use CultuurNet\UDB3\Event\Events\LabelAdded as EventLabelAdded;
use CultuurNet\UDB3\Event\Events\LabelRemoved as EventLabelRemoved;
use CultuurNet\UDB3\Label as LabelValueObject;
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

    protected function setUp(): void
    {
        $this->labelEventRelationTypeResolver = new LabelEventRelationTypeResolver();
    }

    /**
     * @test
     */
    public function it_returns_relation_type_event_for_label_added_on_event(): void
    {
        $labelAdded = new EventLabelAdded('6b96a237-2e00-49a2-ba6d-fc2beab0707e', new LabelValueObject('foo'));

        $this->assertEquals(
            RelationType::EVENT(),
            $this->labelEventRelationTypeResolver->getRelationType($labelAdded)
        );
    }

    /**
     * @test
     */
    public function it_returns_relation_type_event_for_label_removed_from_event(): void
    {
        $labelRemoved = new EventLabelRemoved('6b96a237-2e00-49a2-ba6d-fc2beab0707e', new LabelValueObject('foo'));

        $this->assertEquals(
            RelationType::EVENT(),
            $this->labelEventRelationTypeResolver->getRelationType($labelRemoved)
        );
    }

    /**
     * @test
     */
    public function it_returns_relation_type_place_for_label_added_on_place(): void
    {
        $labelAdded = new PlaceLabelAdded('6b96a237-2e00-49a2-ba6d-fc2beab0707e', new LabelValueObject('foo'));

        $this->assertEquals(
            RelationType::PLACE(),
            $this->labelEventRelationTypeResolver->getRelationType($labelAdded)
        );
    }

    /**
     * @test
     */
    public function it_returns_relation_type_place_for_label_removed_from_place(): void
    {
        $labelRemoved = new PlaceLabelRemoved('6b96a237-2e00-49a2-ba6d-fc2beab0707e', new LabelValueObject('foo'));

        $this->assertEquals(
            RelationType::PLACE(),
            $this->labelEventRelationTypeResolver->getRelationType($labelRemoved)
        );
    }

    /**
     * @test
     */
    public function it_returns_relation_type_organizer_for_label_added_on_organizer(): void
    {
        $labelAdded = new OrganizerLabelAdded('6b96a237-2e00-49a2-ba6d-fc2beab0707e', new LabelValueObject('foo'));

        $this->assertEquals(
            RelationType::ORGANIZER(),
            $this->labelEventRelationTypeResolver->getRelationType($labelAdded)
        );
    }

    /**
     * @test
     */
    public function it_returns_relation_type_organizer_for_label_removed_from_organizer(): void
    {
        $labelRemoved = new OrganizerLabelRemoved('6b96a237-2e00-49a2-ba6d-fc2beab0707e', new LabelValueObject('foo'));

        $this->assertEquals(
            RelationType::ORGANIZER(),
            $this->labelEventRelationTypeResolver->getRelationType($labelRemoved)
        );
    }

    /**
     * @test
     */
    public function it_throws_illegal_argument_for_label_events_other_then_added_or_removed(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $dummyLabelEvent = new DummyLabelEvent('6b96a237-2e00-49a2-ba6d-fc2beab0707e', new LabelValueObject('foo'));
        $this->labelEventRelationTypeResolver->getRelationType($dummyLabelEvent);
    }
}
