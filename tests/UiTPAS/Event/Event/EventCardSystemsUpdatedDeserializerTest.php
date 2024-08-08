<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\UiTPAS\Event\Event;

use CultuurNet\UDB3\Json;
use CultuurNet\UDB3\UiTPAS\ValueObject\Id;
use PHPUnit\Framework\TestCase;

final class EventCardSystemsUpdatedDeserializerTest extends TestCase
{
    private EventCardSystemsUpdatedDeserializer $deserializer;

    public function setUp(): void
    {
        $this->deserializer = new EventCardSystemsUpdatedDeserializer();
    }

    /**
     * @test
     */
    public function it_should_deserialize_a_valid_message(): void
    {
        $json = Json::encode(
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
                    ],
                ],
            ]
        );

        $event = $this->deserializer->deserialize($json);
        $cardSystems = $event->getCardSystems();

        $this->assertEquals(new Id('48ef34b0-e34a-4a15-9ae2-a5a01f189f90'), $event->getId());
        $this->assertEquals(new Id('7'), $cardSystems[7]->getId());
        $this->assertEquals('UiTPAS Oostende', $cardSystems[7]->getName());
        $this->assertEquals(new Id('25'), $cardSystems[25]->getId());
        $this->assertEquals('UiTPAS Dender', $cardSystems[25]->getName());
        $this->assertCount(2, $cardSystems);
    }

    /**
     * @test
     */
    public function it_should_deserialize_a_valid_message_with_only_one_card_system(): void
    {
        $json = Json::encode(
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

        $event = $this->deserializer->deserialize($json);
        $cardSystems = $event->getCardSystems();

        $this->assertEquals(new Id('48ef34b0-e34a-4a15-9ae2-a5a01f189f90'), $event->getId());
        $this->assertEquals(new Id('7'), $cardSystems[7]->getId());
        $this->assertEquals('UiTPAS Oostende', $cardSystems[7]->getName());
        $this->assertCount(1, $cardSystems);
    }

    /**
     * @test
     */
    public function it_should_deserialize_a_valid_message_without_card_systems(): void
    {
        $json = Json::encode(
            [
                'cdbid' => '48ef34b0-e34a-4a15-9ae2-a5a01f189f90',
                'cardSystems' => [],
            ]
        );

        $event = $this->deserializer->deserialize($json);
        $cardSystems = $event->getCardSystems();

        $this->assertEquals(new Id('48ef34b0-e34a-4a15-9ae2-a5a01f189f90'), $event->getId());
        $this->assertCount(0, $cardSystems);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_cdbid_property_is_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $json = Json::encode(
            [
                'cardSystems' => [],
            ]
        );

        $this->deserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_card_systems_property_is_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $json = Json::encode(
            [
                'cdbid' => '48ef34b0-e34a-4a15-9ae2-a5a01f189f90',
            ]
        );

        $this->deserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_card_systems_property_is_not_an_array(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $json = Json::encode(
            [
                'cdbid' => '48ef34b0-e34a-4a15-9ae2-a5a01f189f90',
                'cardSystems' => false,
            ]
        );

        $this->deserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_card_system_id_property_is_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $json = Json::encode(
            [
                'cdbid' => '48ef34b0-e34a-4a15-9ae2-a5a01f189f90',
                'cardSystems' => [
                    [
                        'name' => 'UiTPAS Oostende',
                    ],
                ],
            ]
        );

        $this->deserializer->deserialize($json);
    }

    /**
     * @test
     */
    public function it_should_throw_an_exception_when_the_card_system_name_property_is_missing(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $json = Json::encode(
            [
                'cdbid' => '48ef34b0-e34a-4a15-9ae2-a5a01f189f90',
                'cardSystems' => [
                    [
                        'id' => 7,
                    ],
                ],
            ]
        );

        $this->deserializer->deserialize($json);
    }
}
