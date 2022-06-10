<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use Broadway\Serializer\Serializable;
use CultuurNet\UDB3\Label as LegacyLabel;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use PHPUnit\Framework\TestCase;

class LabelAddedTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_serialized_to_an_array(): void
    {
        $labelsMerged = new LabelAdded(
            'foo',
            new Label(new LabelName('label 1'))
        );

        $this->assertInstanceOf(Serializable::class, $labelsMerged);

        $expectedSerializedEvent = [
            'item_id' => 'foo',
            'label' => 'label 1',
            'visibility' => true,
        ];

        $this->assertEquals(
            $expectedSerializedEvent,
            $labelsMerged->serialize()
        );
    }

    /**
     * @test
     */
    public function it_can_deserialize_an_array()
    {
        $serializedEvent = [
            'item_id' => 'foo',
            'label' => 'label 1',
            'visibility' => true,
        ];

        $expectedEventWasLabelled = new LabelAdded(
            'foo',
            new Label(new LabelName('label 1'))
        );

        $this->assertEquals(
            $expectedEventWasLabelled,
            LabelAdded::deserialize($serializedEvent)
        );
    }
}
