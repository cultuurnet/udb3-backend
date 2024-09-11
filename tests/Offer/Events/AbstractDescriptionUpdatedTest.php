<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Description;
use CultuurNet\UDB3\Offer\Item\Events\DescriptionUpdated;
use PHPUnit\Framework\TestCase;

class AbstractDescriptionUpdatedTest extends TestCase
{
    protected AbstractDescriptionUpdated $descriptionUpdated;

    protected string $itemId;

    protected Description $description;

    public function setUp(): void
    {
        $this->itemId = 'Foo';
        $this->description = new Description('Description');
        $this->descriptionUpdated = new DescriptionUpdated($this->itemId, $this->description);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_With_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedDescription = new Description('Description');
        $expectedDescriptionUpdated = new DescriptionUpdated(
            $expectedItemId,
            $expectedDescription
        );

        $this->assertEquals($expectedDescriptionUpdated, $this->descriptionUpdated);
    }

    /**
     * @test
     */
    public function it_can_return_its_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedDescription = new Description('Description');

        $itemId = $this->descriptionUpdated->getItemId();
        $description = $this->descriptionUpdated->getDescription();

        $this->assertEquals($expectedItemId, $itemId);
        $this->assertEquals($expectedDescription, $description);
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_to_an_array(
        array $expectedSerializedValue,
        DescriptionUpdated $descriptionUpdated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $descriptionUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_deserialize_an_array(
        array $serializedValue,
        DescriptionUpdated $expectedDescriptionUpdated
    ): void {
        $this->assertEquals(
            $expectedDescriptionUpdated,
            DescriptionUpdated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'abstractDescriptionUpdated' => [
                [
                    'item_id' => 'madId',
                    'description' => 'Description',
                ],
                new DescriptionUpdated(
                    'madId',
                    new Description('Description')
                ),
            ],
        ];
    }
}
