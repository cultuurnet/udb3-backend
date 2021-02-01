<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\Offer\AgeRange;
use CultuurNet\UDB3\Offer\Item\Events\TypicalAgeRangeUpdated;
use PHPUnit\Framework\TestCase;
use ValueObjects\Person\Age;

class AbstractTypicalAgeRangeUpdatedTest extends TestCase
{
    /**
     * @var AbstractTypicalAgeRangeUpdated
     */
    protected $typicalAgeRangeUpdated;

    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var string
     */
    protected $typicalAgeRange;

    public function setUp()
    {
        $this->itemId = 'Foo';
        $this->typicalAgeRange = new AgeRange(new Age(3), new Age(12));
        $this->typicalAgeRangeUpdated = new TypicalAgeRangeUpdated($this->itemId, $this->typicalAgeRange);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_With_properties()
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
    public function it_can_return_its_properties()
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
    ) {
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
    ) {
        $this->assertEquals(
            $expectedTypicalAgeRangeUpdated,
            TypicalAgeRangeUpdated::deserialize($serializedValue)
        );
    }

    /**
     * @return array
     */
    public function serializationDataProvider()
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
