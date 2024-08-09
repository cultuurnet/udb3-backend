<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractLabelEventTest extends TestCase
{
    private string $organizerId;

    private Label $label;

    /**
     * @var AbstractLabelEvent&MockObject
     */
    private $abstractLabelEvent;

    protected function setUp(): void
    {
        $this->organizerId = 'organizerId';

        $this->label = new Label(new LabelName('foo'), false);

        $this->abstractLabelEvent = $this->getMockForAbstractClass(
            AbstractLabelEvent::class,
            [$this->organizerId, 'foo', false]
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id(): void
    {
        $this->assertEquals(
            $this->organizerId,
            $this->abstractLabelEvent->getOrganizerId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_label(): void
    {
        $this->assertEquals(
            $this->label->getName()->toString(),
            $this->abstractLabelEvent->getLabelName()
        );

        $this->assertEquals(
            $this->label->isVisible(),
            $this->abstractLabelEvent->isLabelVisible()
        );
    }

    /**
     * @test
     */
    public function it_can_serialize(): void
    {
        $expectedArray = [
            'organizer_id' => $this->organizerId,
            'label' => $this->label->getName()->toString(),
            'visibility' => false,
        ];

        $this->assertEquals(
            $expectedArray,
            $this->abstractLabelEvent->serialize()
        );
    }
}
