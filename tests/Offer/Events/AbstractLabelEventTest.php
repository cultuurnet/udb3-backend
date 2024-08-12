<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\Label;
use CultuurNet\UDB3\Model\ValueObject\Taxonomy\Label\LabelName;
use CultuurNet\UDB3\Offer\Item\Events\LabelAdded;
use PHPUnit\Framework\TestCase;

class AbstractLabelEventTest extends TestCase
{
    /**
     * @var LabelAdded
     */
    protected $labelEvent;

    protected string $itemId;

    protected Label $label;

    public function setUp(): void
    {
        $this->itemId = 'Foo';
        $this->labelEvent = new LabelAdded($this->itemId, 'LabelTest');
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_With_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedLabelEvent = new LabelAdded($expectedItemId, 'LabelTest');

        $this->assertEquals($expectedLabelEvent, $this->labelEvent);
    }

    /**
     * @test
     */
    public function it_can_return_its_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedLabel = new Label(new LabelName('LabelTest'));

        $this->assertEquals($expectedItemId, $this->labelEvent->getItemId());
        $this->assertEquals($expectedLabel->getName()->toString(), $this->labelEvent->getLabelName());
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_to_an_array(
        array $expectedSerializedValue,
        LabelAdded $abstractLabelEvent
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $abstractLabelEvent->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_deserialize_an_array(
        array $serializedValue,
        LabelAdded $expectedAbstractLabelEvent
    ): void {
        $this->assertEquals(
            $expectedAbstractLabelEvent,
            LabelAdded::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'abstractLabelEvent' => [
                [
                    'item_id' => 'madId',
                    'label' => 'label123',
                    'visibility' => true,
                ],
                new LabelAdded(
                    'madId',
                    'label123'
                ),
            ],
            'abstractLabelEvent2' => [
                [
                    'item_id' => 'madId',
                    'label' => 'label123',
                    'visibility' => false,
                ],
                new LabelAdded(
                    'madId',
                    'label123',
                    false
                ),
            ],
        ];
    }
}
