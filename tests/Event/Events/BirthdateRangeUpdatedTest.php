<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Event\Events;

use CultuurNet\UDB3\Model\ValueObject\Audience\BirthdateRange;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BirthdateRangeUpdatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        BirthdateRangeUpdated $birthdateRangeUpdated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $birthdateRangeUpdated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        BirthdateRangeUpdated $expectedBirthdateRangeUpdated
    ): void {
        $this->assertEquals(
            $expectedBirthdateRangeUpdated,
            BirthdateRangeUpdated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'birthdate range' => [
                [
                    'item_id' => 'foo',
                    'birthdateRange' => [
                        'from' => '2014-01-01',
                        'to' => '2020-12-31',
                    ],
                ],
                new BirthdateRangeUpdated(
                    'foo',
                    new BirthdateRange(
                        new DateTimeImmutable('2014-01-01'),
                        new DateTimeImmutable('2020-12-31')
                    )
                ),
            ],
        ];
    }
}
