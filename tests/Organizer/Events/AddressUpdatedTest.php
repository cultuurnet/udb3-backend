<?php

declare(strict_types=1);

namespace CultuurNet\UDB3\Organizer\Events;

use PHPUnit\Framework\TestCase;

class AddressUpdatedTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_be_serialized_and_deserialized(): void
    {
        $addressUpdated = new AddressUpdated(
            '0460ffbd-1c85-4bad-9a8f-be1f981648e7',
            'Martelarenplein 12',
            '3000',
            'Leuven',
            'BE'
        );

        $data = $addressUpdated->serialize();
        $deserialized = AddressUpdated::deserialize($data);

        $this->assertEquals($addressUpdated, $deserialized);
    }
}
