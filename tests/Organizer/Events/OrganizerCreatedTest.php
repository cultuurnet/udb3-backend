<?php

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use CultuurNet\UDB3\Title;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;

class OrganizerCreatedTest extends TestCase
{
    /**
     * @test
     * @dataProvider serializationDataProvider
     * @param array $expectedSerializedValue
     * @param OrganizerCreated $organizerCreated
     */
    public function it_can_be_serialized_into_an_array(
        $expectedSerializedValue,
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
     * @param array $serializedValue
     * @param OrganizerCreated $expectedOrganizerCreated
     */
    public function it_can_be_deserialized_from_an_array(
        $serializedValue,
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
                    new Title('title'),
                    array(
                        new Address(
                            new Street('Kerkstraat 69'),
                            new PostalCode('3000'),
                            new Locality('Leuven'),
                            Country::fromNative('BE')
                        ),
                    ),
                    array('0123456789'),
                    array('foo@bar.com'),
                    array('http://foo.bar')
                ),
            ],
        ];
    }
}
