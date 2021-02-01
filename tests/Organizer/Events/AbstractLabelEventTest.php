<?php

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Label;
use PHPUnit\Framework\TestCase;

class AbstractLabelEventTest extends TestCase
{
    /**
     * @var string
     */
    private $organizerId;

    /**
     * @var Label
     */
    private $label;

    /**
     * @var AbstractLabelEvent
     */
    private $abstractLabelEvent;

    protected function setUp()
    {
        $this->organizerId = 'organizerId';

        $this->label = new Label('foo', false);

        $this->abstractLabelEvent = $this->getMockForAbstractClass(
            AbstractLabelEvent::class,
            [$this->organizerId, $this->label]
        );
    }

    /**
     * @test
     */
    public function it_stores_an_organizer_id()
    {
        $this->assertEquals(
            $this->organizerId,
            $this->abstractLabelEvent->getOrganizerId()
        );
    }

    /**
     * @test
     */
    public function it_stores_a_label()
    {
        $this->assertEquals(
            $this->label,
            $this->abstractLabelEvent->getLabel()
        );
    }

    /**
     * @test
     */
    public function it_can_serialize()
    {
        $expectedArray = [
            'organizer_id' => $this->organizerId,
            'label' => (string) $this->label,
            'visibility' => false,
        ];

        $this->assertEquals(
            $expectedArray,
            $this->abstractLabelEvent->serialize()
        );
    }
}
