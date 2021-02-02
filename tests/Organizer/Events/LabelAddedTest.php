<?php

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Label;
use PHPUnit\Framework\TestCase;

class LabelAddedTest extends TestCase
{
    /**
     * @var LabelAdded
     */
    private $labelAdded;

    protected function setUp()
    {
        $this->labelAdded = new LabelAdded('organizerId', new Label('foo', false));
    }

    /**
     * @test
     */
    public function it_derives_from_abstract_label_event()
    {
        $this->assertInstanceOf(AbstractLabelEvent::class, $this->labelAdded);
    }

    /**
     * @test
     */
    public function it_can_deserialize()
    {
        $labelAddesAsArray = [
            'organizer_id' => 'organizerId',
            'label' => 'foo',
            'visibility' => false,
        ];

        $this->assertEquals(
            $this->labelAdded,
            LabelAdded::deserialize($labelAddesAsArray)
        );
    }
}
