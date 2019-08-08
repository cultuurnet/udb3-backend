<?php

namespace CultuurNet\UDB3\UiTPAS\Event\Event;

use CultuurNet\UDB3\UiTPAS\ValueObject\Id;
use ValueObjects\StringLiteral\StringLiteral;

class EventCardSystemsUpdatedDeserializerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EventCardSystemsUpdatedDeserializer
     */
    private $deserializer;

    public function setUp()
    {
        $this->deserializer = new EventCardSystemsUpdatedDeserializer();
    }

    /**
     * @test
     */
    public function it_should_deserialize_a_valid_message()
    {
        $json = json_encode(
            [
                'cdbid' => '48ef34b0-e34a-4a15-9ae2-a5a01f189f90',
                'cardSystems' => [
                    [
                        'id' => 7,
                        'name' => 'UiTPAS Oostende',
                    ],
                    [
                        'id' => 25,
                        'name' => 'UiTPAS Dender',
                    ]
                ],
            ]
        );

        $event = $this->deserializer->deserialize(new StringLiteral($json));
        $cardSystems = $event->getCardSystems();

        $this->assertEquals('48ef34b0-e34a-4a15-9ae2-a5a01f189f90', $event->getId());
        $this->assertEquals(new Id('7'), $cardSystems->getByKey(7)->getId());
        $this->assertEquals(new StringLiteral('UiTPAS Oostende'), $cardSystems->getByKey(7)->getName());
        $this->assertEquals(new Id('25'), $cardSystems->getByKey(25)->getId());
        $this->assertEquals(new StringLiteral('UiTPAS Dender'), $cardSystems->getByKey(25)->getName());
        $this->assertCount(2, $cardSystems->toArray());
    }

    /**
     * @test
     */
    public function it_should_deserialize_a_valid_message_with_only_one_card_system()
    {
        $json = json_encode(
            [
                'cdbid' => '48ef34b0-e34a-4a15-9ae2-a5a01f189f90',
                'cardSystems' => [
                    [
                        'id' => 7,
                        'name' => 'UiTPAS Oostende',
                    ],
                ],
            ]
        );

        $event = $this->deserializer->deserialize(new StringLiteral($json));
        $cardSystems = $event->getCardSystems();

        $this->assertEquals('48ef34b0-e34a-4a15-9ae2-a5a01f189f90', $event->getId());
        $this->assertEquals(new Id('7'), $cardSystems->getByKey(7)->getId());
        $this->assertEquals(new StringLiteral('UiTPAS Oostende'), $cardSystems->getByKey(7)->getName());
        $this->assertCount(1, $cardSystems->toArray());
    }

    /**
     * @test
     */
    public function it_should_deserialize_a_valid_message_without_card_systems()
    {
        $json = json_encode(
            [
                'cdbid' => '48ef34b0-e34a-4a15-9ae2-a5a01f189f90',
                'cardSystems' => [],
            ]
        );

        $event = $this->deserializer->deserialize(new StringLiteral($json));
        $cardSystems = $event->getCardSystems();

        $this->assertEquals('48ef34b0-e34a-4a15-9ae2-a5a01f189f90', $event->getId());
        $this->assertCount(0, $cardSystems->toArray());
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_cdbid_property_is_missing()
    {
        $this->expectException(\InvalidArgumentException::class);

        $json = json_encode(
            [
                'cardSystems' => [],
            ]
        );

        $this->deserializer->deserialize(new StringLiteral($json));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_card_systems_property_is_missing()
    {
        $this->expectException(\InvalidArgumentException::class);

        $json = json_encode(
            [
                'cdbid' => '48ef34b0-e34a-4a15-9ae2-a5a01f189f90',
            ]
        );

        $this->deserializer->deserialize(new StringLiteral($json));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_card_systems_property_is_not_an_array()
    {
        $this->expectException(\InvalidArgumentException::class);

        $json = json_encode(
            [
                'cdbid' => '48ef34b0-e34a-4a15-9ae2-a5a01f189f90',
                'cardSystems' => false,
            ]
        );

        $this->deserializer->deserialize(new StringLiteral($json));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_card_system_id_property_is_missing()
    {
        $this->expectException(\InvalidArgumentException::class);

        $json = json_encode(
            [
                'cdbid' => '48ef34b0-e34a-4a15-9ae2-a5a01f189f90',
                'cardSystems' => [
                    [
                        'name' => 'UiTPAS Oostende',
                    ],
                ],
            ]
        );

        $this->deserializer->deserialize(new StringLiteral($json));
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_card_system_name_property_is_missing()
    {
        $this->expectException(\InvalidArgumentException::class);

        $json = json_encode(
            [
                'cdbid' => '48ef34b0-e34a-4a15-9ae2-a5a01f189f90',
                'cardSystems' => [
                    [
                        'id' => 7,
                    ],
                ],
            ]
        );

        $this->deserializer->deserialize(new StringLiteral($json));
    }
}
