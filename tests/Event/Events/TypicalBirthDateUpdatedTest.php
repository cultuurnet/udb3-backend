<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class TypicalBirthDateUpdatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        TypicalBirthDateUpdated $typicalBirthDateUpdated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $typicalBirthDateUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        TypicalBirthDateUpdated $expectedTypicalBirthDateUpdated
    ): void {
        $this->assertEquals(
            $expectedTypicalBirthDateUpdated,
            TypicalBirthDateUpdated::deserialize($serializedValue)
        );
    }

    /**
     * @test
     */
    public function it_returns_the_typical_birth_date(): void
    {
        $birthDate = new DateTimeImmutable('2020-03-15');
        $event = new TypicalBirthDateUpdated('foo', $birthDate);

        $this->assertEquals($birthDate, $event->getTypicalBirthDate());
    }

    public function serializationDataProvider(): array
    {
        return [
            'typical birth date' => [
                [
                    'item_id' => 'foo',
                    'typicalBirthDate' => '2020-03-15',
                ],
                new TypicalBirthDateUpdated(
                    'foo',
                    new DateTimeImmutable('2020-03-15')
                ),
            ],
        ];
    }
}
