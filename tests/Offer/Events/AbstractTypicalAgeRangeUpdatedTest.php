<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Model\ValueObject\Audience\Age;
use CultuurNet\UDB3\Model\ValueObject\Audience\AgeRange;
use CultuurNet\UDB3\Offer\Item\Events\TypicalAgeRangeUpdated;
use PHPUnit\Framework\TestCase;

class AbstractTypicalAgeRangeUpdatedTest extends TestCase
{
    /**
     * @var AbstractTypicalAgeRangeUpdated
     */
    protected $typicalAgeRangeUpdated;

    protected string $itemId;

    protected AgeRange $typicalAgeRange;

    public function setUp(): void
    {
        $this->itemId = 'Foo';
        $this->typicalAgeRange = new AgeRange(new Age(3), new Age(12));
        $this->typicalAgeRangeUpdated = new TypicalAgeRangeUpdated($this->itemId, $this->typicalAgeRange);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_With_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedTypicalAgeRange = new AgeRange(new Age(3), new Age(12));
        $expectedTypicalAgeRangeUpdated = new TypicalAgeRangeUpdated(
            $expectedItemId,
            $expectedTypicalAgeRange
        );

        $this->assertEquals($expectedTypicalAgeRangeUpdated, $this->typicalAgeRangeUpdated);
    }

    /**
     * @test
     */
    public function it_can_return_its_properties(): void
    {
        $expectedItemId = 'Foo';
        $expectedTypicalAgeRange = new AgeRange(new Age(3), new Age(12));

        $itemId = $this->typicalAgeRangeUpdated->getItemId();
        $typicalAgeRange = $this->typicalAgeRangeUpdated->getTypicalAgeRange();

        $this->assertEquals($expectedItemId, $itemId);
        $this->assertEquals($expectedTypicalAgeRange, $typicalAgeRange);
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_to_an_array(
        array $expectedSerializedValue,
        TypicalAgeRangeUpdated $typicalAgeRangeUpdated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $typicalAgeRangeUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_deserialize_an_array(
        array $serializedValue,
        TypicalAgeRangeUpdated $expectedTypicalAgeRangeUpdated
    ): void {
        $this->assertEquals(
            $expectedTypicalAgeRangeUpdated,
            TypicalAgeRangeUpdated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'abstractTypicalAgeRangeUpdated' => [
                [
                    'item_id' => 'madId',
                    'typicalAgeRange' => '3-12',
                ],
                new TypicalAgeRangeUpdated(
                    'madId',
                    new AgeRange(new Age(3), new Age(12))
                ),
            ],
        ];
    }
}
