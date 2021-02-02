<?php

namespace CultuurNet\UDB3\Organizer\Events;

use CultuurNet\UDB3\Address\Address;
use CultuurNet\UDB3\Address\Locality;
use CultuurNet\UDB3\Address\PostalCode;
use CultuurNet\UDB3\Address\Street;
use PHPUnit\Framework\TestCase;
use ValueObjects\Geography\Country;

class AddressUpdatedTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_serialized_and_deserialized()
    {
        $addressUpdated = new AddressUpdated(
            '0460ffbd-1c85-4bad-9a8f-be1f981648e7',
            new Address(
                new Street('Martelarenplein 12'),
                new PostalCode('3000'),
                new Locality('Leuven'),
                Country::fromNative('BE')
            )
        );

        $data = $addressUpdated->serialize();
        $deserialized = AddressUpdated::deserialize($data);

        $this->assertEquals($addressUpdated, $deserialized);
    }
}
