<?php

namespace CultuurNet\UDB3\Offer\Events;

use CultuurNet\UDB3\ContactPoint;
use CultuurNet\UDB3\Offer\Item\Events\ContactPointUpdated;
use PHPUnit\Framework\TestCase;

class AbstractContactPointUpdatedTest extends TestCase
{
    /**
     * @var AbstractContactPointUpdated
     */
    protected $contactPointUpdated;

    /**
     * @var string
     */
    protected $itemId;

    /**
     * @var ContactPoint
     */
    protected $contactPoint;

    public function setUp()
    {
        $this->itemId = 'Foo';
        $this->contactPoint = new ContactPoint(
            ['0123456789'],
            ['foo@bar.com'],
            ['http://foo.bar']
        );
        $this->contactPointUpdated = new ContactPointUpdated($this->itemId, $this->contactPoint);
    }

    /**
     * @test
     */
    public function it_can_be_instantiated_With_properties()
    {
        $expectedItemId = 'Foo';
        $expectedContactPoint = new ContactPoint(
            ['0123456789'],
            ['foo@bar.com'],
            ['http://foo.bar']
        );
        $expectedContactPointUpdated = new ContactPointUpdated(
            $expectedItemId,
            $expectedContactPoint
        );

        $this->assertEquals($expectedContactPointUpdated, $this->contactPointUpdated);
    }

    /**
     * @test
     */
    public function it_can_return_its_properties()
    {
        $expectedItemId = 'Foo';
        $expectedContactPoint = new ContactPoint(
            ['0123456789'],
            ['foo@bar.com'],
            ['http://foo.bar']
        );

        $itemId = $this->contactPointUpdated->getItemId();
        $contactPoint = $this->contactPointUpdated->getContactPoint();

        $this->assertEquals($expectedItemId, $itemId);
        $this->assertEquals($expectedContactPoint, $contactPoint);
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_to_an_array(
        array $expectedSerializedValue,
        ContactPointUpdated $contactPointUpdated
    ) {
        $this->assertEquals(
            $expectedSerializedValue,
            $contactPointUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_deserialize_an_array(
        array $serializedValue,
        ContactPointUpdated $expectedContactPointUpdated
    ) {
        $this->assertEquals(
            $expectedContactPointUpdated,
            ContactPointUpdated::deserialize($serializedValue)
        );
    }

    /**
     * @return array
     */
    public function serializationDataProvider()
    {
        return [
            'abstractContactPointUpdated' => [
                [
                    'item_id' => 'madId',
                    'contactPoint' => [
                        'phone' => [
                            '0123456789',
                        ],
                        'email' => [
                            'foo@bar.com',
                        ],
                        'url' => [
                            'http://foo.bar',
                        ],
                    ],
                ],
                new ContactPointUpdated(
                    'madId',
                    new ContactPoint(
                        array('0123456789'),
                        array('foo@bar.com'),
                        array('http://foo.bar')
                    )
                ),
            ],
        ];
    }
}
