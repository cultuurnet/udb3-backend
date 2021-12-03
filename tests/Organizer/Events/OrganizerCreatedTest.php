<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

class OrganizerCreatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_serialized_into_an_array(
        array $expectedSerializedValue,
        OrganizerCreated $organizerCreated
    ): void {
        $this->assertEquals(
            $expectedSerializedValue,
            $organizerCreated->serialize()
        );
    }

    /**
     * @test
     * @dataProvider serializationDataProvider
     */
    public function it_can_be_deserialized_from_an_array(
        array $serializedValue,
        OrganizerCreated $expectedOrganizerCreated
    ): void {
        $this->assertEquals(
            $expectedOrganizerCreated,
            OrganizerCreated::deserialize($serializedValue)
        );
    }

    public function serializationDataProvider(): array
    {
        return [
            'organizerCreated' => [
                [
                    'organizer_id' => 'organizer_id',
                    'title' => 'title',
                    'addresses' => [
                        0 => [
                            'streetAddress' => 'Kerkstraat 69',
                            'postalCode' => '3000',
                            'addressLocality' => 'Leuven',
                            'addressCountry' => 'BE',
                        ],
                    ],
                    'phones' => [
                        '0123456789',
                    ],
                    'emails' => [
                        'foo@bar.com',
                    ],
                    'urls' => [
                        'http://foo.bar',
                    ],
                ],
                new OrganizerCreated(
                    'organizer_id',
                    'title',
                    'Kerkstraat 69',
                    '3000',
                    'Leuven',
                    'BE',
                    ['0123456789'],
                    ['foo@bar.com'],
                    ['http://foo.bar'],
                ),
            ],
        ];
    }
}
